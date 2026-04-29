<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Tag;
use App\Crm\Models\Task as CrmTask;
use Tests\TestCase;

class CrmContactsModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_contacts_index_filters_records(): void
    {
        Contact::factory()->create(['full_name' => 'Alice Customer', 'email' => 'alice@example.com']);
        Contact::factory()->create(['full_name' => 'Bob Prospect', 'email' => 'bob@example.com']);

        $this->actingAs($this->admin, 'admin')
            ->get('/admin/crm/contacts?search=Alice')
            ->assertOk()
            ->assertSee('Alice Customer')
            ->assertDontSee('Bob Prospect');
    }

    public function test_contact_can_be_created_updated_and_deleted(): void
    {
        $company = Company::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->post('/admin/crm/contacts', [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'full_name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'phone' => '+905551112233',
                'title' => 'CTO',
                'company_id' => $company->id,
                'owner_id' => $this->admin->id,
                'lifecycle_stage' => 'lead',
                'source' => 'website',
                'tag_ids' => [$tag->id],
                'custom_fields_json' => '{"preferred_channel":"email"}',
            ]);

        $contact = Contact::query()->where('email', 'ada@example.com')->firstOrFail();

        $response->assertRedirect(route('crm.contacts.show', $contact));
        $this->assertSame('Ada Lovelace', $contact->full_name);
        $this->assertTrue($contact->tags->contains($tag));

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.contacts.update', $contact), [
                'first_name' => 'Ada',
                'last_name' => 'Byron',
                'full_name' => 'Ada Byron',
                'email' => 'ada@example.com',
                'phone' => '+905551112233',
                'title' => 'CEO',
                'company_id' => $company->id,
                'owner_id' => $this->admin->id,
                'lifecycle_stage' => 'customer',
                'source' => 'referral',
                'tag_ids' => [],
                'custom_fields_json' => '{"preferred_channel":"phone"}',
            ])
            ->assertRedirect(route('crm.contacts.show', $contact));

        $contact->refresh();
        $this->assertSame('Ada Byron', $contact->full_name);
        $this->assertSame('customer', $contact->lifecycle_stage);
        $this->assertSame(['preferred_channel' => 'phone'], $contact->custom_fields);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.contacts.destroy', $contact))
            ->assertRedirect(route('crm.contacts.index'));

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Contact::factory()->create(['email' => 'duplicate@example.com']);

        $this->actingAs($this->admin, 'admin')
            ->from('/admin/crm/contacts/create')
            ->post('/admin/crm/contacts', [
                'full_name' => 'Duplicate Contact',
                'email' => 'duplicate@example.com',
                'lifecycle_stage' => 'lead',
            ])
            ->assertRedirect('/admin/crm/contacts/create')
            ->assertSessionHasErrors('email');
    }

    public function test_contact_show_includes_360_degree_context_and_quick_note(): void
    {
        $contact = Contact::factory()->create(['owner_id' => $this->admin->id]);
        Deal::factory()->create(['contact_id' => $contact->id, 'value' => 12500, 'status' => 'open']);
        Quote::factory()->create(['contact_id' => $contact->id, 'quote_number' => 'CRM-999001']);
        CrmTask::factory()->create([
            'taskable_type' => $contact->getMorphClass(),
            'taskable_id' => $contact->id,
            'title' => 'Call customer',
            'completed_at' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.contacts.show', $contact))
            ->assertOk()
            ->assertSee($contact->full_name)
            ->assertSee(__('Open Deal Value'))
            ->assertSee('Call customer')
            ->assertSee('CRM-999001');

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.notes.store', $contact), ['body' => 'Customer prefers Friday calls.'])
            ->assertRedirect(route('crm.contacts.show', $contact));

        $this->assertDatabaseHas('activities', [
            'activityable_type' => $contact->getMorphClass(),
            'activityable_id' => $contact->id,
            'body' => 'Customer prefers Friday calls.',
        ]);
        $this->assertSame(1, Activity::query()->where('activityable_id', $contact->id)->count());
    }

    public function test_contacts_can_be_imported_with_error_report(): void
    {
        $file = UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", [
            'full_name,email,phone,lifecycle_stage,source',
            'Valid Contact,valid@example.com,+905551112233,lead,website',
            'Broken Contact,not-an-email,+90555,lead,website',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.contacts.import'))
            ->assertSessionHas('crm_import_result');

        $this->assertDatabaseHas('contacts', ['email' => 'valid@example.com']);
        $this->assertDatabaseMissing('contacts', ['email' => 'not-an-email']);

        $result = session('crm_import_result');
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['failed']);
    }

    public function test_contacts_can_be_exported_and_bulk_tagged(): void
    {
        $contact = Contact::factory()->create(['full_name' => 'Export Me', 'email' => 'export@example.com']);
        $tag = Tag::factory()->create(['name' => 'VIP']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.bulk-tags'), [
                'contact_ids' => [$contact->id],
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect();

        $this->assertTrue($contact->refresh()->tags->contains($tag));

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('export@example.com', $response->streamedContent());
    }

    public function test_contacts_can_be_bulk_deleted(): void
    {
        $first = Contact::factory()->create();
        $second = Contact::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.contacts.bulk-delete'), [
                'contact_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('contacts', ['id' => $first->id]);
        $this->assertSoftDeleted('contacts', ['id' => $second->id]);
    }
}
