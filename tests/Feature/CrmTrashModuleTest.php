<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTrashModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_trash_screen_requires_settings_permission(): void
    {
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.trash.index'))
            ->assertOk()
            ->assertSee(__('Trash'));

        $this->actingAs($sales, 'admin')
            ->get(route('crm.trash.index'))
            ->assertForbidden();
    }

    public function test_deleted_records_are_listed_per_module(): void
    {
        $contact = Contact::factory()->create(['full_name' => 'Silinen Kisi']);
        $contact->delete();

        $company = Company::factory()->create(['name' => 'Silinen Sirket']);
        $company->delete();

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.trash.index', ['module' => 'contacts']))
            ->assertOk()
            ->assertSee('Silinen Kisi')
            ->assertDontSee('Silinen Sirket');

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.trash.index', ['module' => 'companies']))
            ->assertOk()
            ->assertSee('Silinen Sirket');
    }

    public function test_record_can_be_restored(): void
    {
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.trash.restore', ['module' => 'contacts', 'id' => $contact->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'deleted_at' => null]);
    }

    public function test_record_can_be_permanently_deleted(): void
    {
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->actingAs($this->owner, 'admin')
            ->delete(route('crm.trash.destroy', ['module' => 'contacts', 'id' => $contact->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_unknown_module_returns_404(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.trash.restore', ['module' => 'tags', 'id' => 1]))
            ->assertNotFound();
    }
}
