<?php

namespace Tests\Feature;

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
            ->assertSee('CRM Engine');
    }

    public function test_crm_config_defaults_are_registered(): void
    {
        $this->assertSame('admin/crm', config('crm.routes.admin_prefix'));
        $this->assertSame('single', config('crm.tenancy.mode'));
        $this->assertSame('TRY', config('crm.money.currency'));
        $this->assertSame('openai', config('crm.ai.driver'));
        $this->assertSame('gpt-4o-mini', config('crm.ai.drivers.openai.model'));
        $this->assertArrayHasKey('claude', config('crm.ai.drivers'));
        $this->assertArrayHasKey('gemini', config('crm.ai.drivers'));
    }
}
