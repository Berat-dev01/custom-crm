<?php

namespace Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Sanalkopru\Crm\CrmServiceProvider;
use Tests\TestCase;

class AdminCrmBootstrapTest extends TestCase
{
    public function test_admin_entry_redirects_to_crm_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/crm');
    }

    public function test_crm_dashboard_route_is_available(): void
    {
        $this->get('/admin/crm')
            ->assertOk()
            ->assertSee('Package dashboard route is ready.');
    }

    public function test_crm_api_health_route_is_available(): void
    {
        $this->getJson('/api/crm/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_crm_config_defaults_are_registered(): void
    {
        $this->assertSame('admin/crm', config('crm.routes.admin_prefix'));
        $this->assertSame(['web'], config('crm.routes.middleware'));
        $this->assertSame('single', config('crm.tenancy.mode'));
        $this->assertSame('TRY', config('crm.money.currency'));
        $this->assertSame('openai', config('crm.ai.driver'));
        $this->assertSame('gpt-4o-mini', config('crm.ai.drivers.openai.model'));
        $this->assertArrayHasKey('claude', config('crm.ai.drivers'));
        $this->assertArrayHasKey('gemini', config('crm.ai.drivers'));
    }

    public function test_crm_package_publish_tags_are_registered(): void
    {
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(CrmServiceProvider::class, 'crm-config'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(CrmServiceProvider::class, 'crm-views'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(CrmServiceProvider::class, 'crm-migrations'));
        $this->assertNotEmpty(ServiceProvider::pathsToPublish(CrmServiceProvider::class, 'crm-assets'));
    }
}
