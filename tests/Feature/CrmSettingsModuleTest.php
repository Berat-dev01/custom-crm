<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmSetting;
use App\Crm\Models\Quote;
use App\Crm\Services\Ai\AiDriverManager;
use App\Crm\Services\Configuration\MoneySettings;
use App\Crm\Services\Quotes\QuoteNumberGenerator;
use App\Crm\Services\Quotes\QuotePdfRenderer;
use App\Crm\Services\Settings\CrmSettingsManager;
use App\Crm\Support\Ai\AiDriver;
use Tests\TestCase;

class CrmSettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_only_settings_users_can_open_settings_screen(): void
    {
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.settings.index'))
            ->assertOk()
            ->assertSee(__('CRM Settings'));

        $this->actingAs($sales, 'admin')
            ->get(route('crm.settings.index'))
            ->assertForbidden();
    }

    public function test_settings_override_quote_branding_money_and_ai_defaults(): void
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('crm-logo.png', 120, 60);

        $this->actingAs($this->owner, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Sanal Kopru CRM',
                'company_logo' => $logo,
                'company_email' => 'sales@sanalkopru.test',
                'company_phone' => '+90 212 000 00 00',
                'company_address' => 'Levent Mah. CRM Sok. No:1 Istanbul',
                'tax_number' => '9876543210',
                'tax_office' => 'Besiktas',
                'default_currency' => 'USD',
                'default_tax_rate' => '8.5',
                'quote_prefix' => 'SK-',
                'quote_terms' => 'Default settings terms.',
                'notify_task_reminders' => '1',
                'notify_task_assignments' => '0',
                'notify_quote_status_changes' => '0',
                'notify_import_status_updates' => '1',
                'ai_enabled' => '1',
                'ai_driver' => 'gemini',
                'ai_model' => 'gemini-test-model',
            ])
            ->assertRedirect(route('crm.settings.index'));

        $this->assertDatabaseHas('crm_settings', [
            'key' => 'company_name',
            'group' => 'company',
        ]);
        $this->assertDatabaseHas('crm_settings', [
            'key' => 'quote_prefix',
            'group' => 'quotes',
        ]);

        $logoPath = data_get(
            CrmSetting::query()->where('key', 'company_logo_path')->value('value'),
            'value'
        );
        Storage::disk('public')->assertExists($logoPath);

        $money = app(MoneySettings::class);
        $this->assertSame('USD', $money->defaultCurrency());
        $this->assertSame(8.5, $money->defaultTaxRate());
        $this->assertSame('SK-', $money->quoteNumberPrefix());
        $this->assertSame('Default settings terms.', $money->quoteTerms());
        $this->assertStringStartsWith('SK-', app(QuoteNumberGenerator::class)->next());

        $renderer = app(QuotePdfRenderer::class);
        $this->assertSame('Sanal Kopru CRM', $renderer->companyProfile()['name']);
        $this->assertSame('9876543210', $renderer->companyProfile()['tax_number']);
        $this->assertNotNull($renderer->logoPath());
        $this->assertFalse((bool) app(CrmSettingsManager::class)->get('notify_task_assignments'));
        $this->assertTrue((bool) app(CrmSettingsManager::class)->get('notify_import_status_updates'));

        $ai = app(AiDriverManager::class);
        $this->assertTrue($ai->enabled());
        $this->assertSame(AiDriver::Gemini, $ai->selected());
        $this->assertSame('gemini-test-model', $ai->model());
    }

    public function test_uploaded_logo_is_re_encoded_and_payload_stripped(): void
    {
        Storage::fake('public');

        $image = imagecreatetruecolor(40, 20);
        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        $file = UploadedFile::fake()->createWithContent('logo.png', $binary.'<?php echo "payload";');

        $this->actingAs($this->owner, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Sanal Kopru CRM',
                'company_logo' => $file,
                'default_currency' => 'TRY',
                'default_tax_rate' => '20',
                'quote_prefix' => 'CRM-',
                'ai_driver' => 'null',
            ])
            ->assertRedirect(route('crm.settings.index'));

        $logoPath = data_get(
            CrmSetting::query()->where('key', 'company_logo_path')->value('value'),
            'value'
        );
        Storage::disk('public')->assertExists($logoPath);

        $stored = (string) Storage::disk('public')->get($logoPath);
        $this->assertStringNotContainsString('<?php', $stored);
        $this->assertNotFalse(@imagecreatefromstring($stored));
    }

    public function test_quote_preview_uses_settings_company_profile(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Preview CRM Ltd',
                'company_email' => 'quotes@preview.test',
                'company_phone' => '+90 555 111 22 33',
                'company_address' => 'PDF Street',
                'tax_number' => '1112223334',
                'tax_office' => 'Kadikoy',
                'default_currency' => 'TRY',
                'default_tax_rate' => '20',
                'quote_prefix' => 'PV-',
                'quote_terms' => 'Preview settings terms.',
                'notify_task_reminders' => '1',
                'notify_task_assignments' => '1',
                'notify_quote_status_changes' => '1',
                'notify_import_status_updates' => '1',
                'ai_enabled' => '0',
                'ai_driver' => 'openai',
                'ai_model' => '',
            ]);

        $company = Company::factory()->create(['name' => 'Customer Co']);
        $contact = Contact::factory()->create(['company_id' => $company->id]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'terms' => null,
        ]);

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.quotes.preview', $quote))
            ->assertOk()
            ->assertSee('Preview CRM Ltd')
            ->assertSee('1112223334');
    }

    public function test_logo_upload_must_be_a_safe_image(): void
    {
        $file = UploadedFile::fake()->createWithContent('logo.svg', '<svg></svg>');

        $this->actingAs($this->owner, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Invalid Logo Co',
                'company_logo' => $file,
                'default_currency' => 'TRY',
                'default_tax_rate' => '20',
                'quote_prefix' => 'CRM-',
                'notify_task_reminders' => '1',
                'notify_task_assignments' => '1',
                'notify_quote_status_changes' => '1',
                'notify_import_status_updates' => '1',
                'ai_enabled' => '0',
                'ai_driver' => 'openai',
            ])
            ->assertSessionHasErrors('company_logo');
    }
}
