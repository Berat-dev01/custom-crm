<?php

namespace Tests\Unit;

use Sanalkopru\Crm\Services\Configuration\FeatureManager;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;
use Sanalkopru\Crm\Services\Configuration\UiSettings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class CrmConfigurationServicesTest extends TestCase
{
    public function test_feature_manager_reads_module_flags(): void
    {
        config([
            'crm.modules.contacts' => true,
            'crm.modules.ai' => false,
        ]);

        $features = app(FeatureManager::class);

        $this->assertTrue($features->enabled('contacts'));
        $this->assertTrue($features->disabled('ai'));
        $this->assertContains('contacts', $features->enabledModules());
        $this->assertNotContains('ai', $features->enabledModules());
    }

    public function test_feature_manager_blocks_disabled_modules(): void
    {
        config(['crm.modules.quotes' => false]);

        $this->expectException(NotFoundHttpException::class);

        app(FeatureManager::class)->ensureEnabled('quotes');
    }

    public function test_money_settings_reads_currency_and_quote_defaults(): void
    {
        config([
            'crm.money.default_currency' => 'TRY',
            'crm.money.supported_currencies' => ['TRY', 'USD', 'EUR'],
            'crm.money.default_tax_rate' => 20.0,
            'crm.quotes.number_prefix' => 'CRM-',
            'crm.quotes.number_padding' => 6,
        ]);

        $money = app(MoneySettings::class);

        $this->assertSame('TRY', $money->defaultCurrency());
        $this->assertTrue($money->supportsCurrency('USD'));
        $this->assertFalse($money->supportsCurrency('GBP'));
        $this->assertSame(20.0, $money->defaultTaxRate());
        $this->assertSame('CRM-', $money->quoteNumberPrefix());
        $this->assertSame(6, $money->quoteNumberPadding());
    }

    public function test_ui_settings_reads_branding_defaults(): void
    {
        config([
            'crm.ui.app_name' => 'CRM Engine',
            'crm.ui.primary_color' => '#2563eb',
        ]);

        $ui = app(UiSettings::class);

        $this->assertSame('CRM Engine', $ui->appName());
        $this->assertSame('#2563eb', $ui->primaryColor());
    }
}
