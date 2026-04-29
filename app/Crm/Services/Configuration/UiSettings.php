<?php

namespace App\Crm\Services\Configuration;

use App\Crm\Services\Settings\CrmSettingsManager;

class UiSettings
{
    public function __construct(private readonly CrmSettingsManager $settings) {}

    public function appName(): string
    {
        return (string) $this->settings->get('company_name', config('crm.ui.app_name', config('app.name', 'CRM Engine')));
    }

    public function primaryColor(): string
    {
        return (string) config('crm.ui.primary_color', '#2563eb');
    }
}
