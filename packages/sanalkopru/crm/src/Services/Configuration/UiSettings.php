<?php

namespace Sanalkopru\Crm\Services\Configuration;

class UiSettings
{
    public function appName(): string
    {
        return (string) config('crm.ui.app_name', config('app.name', 'CRM Engine'));
    }

    public function primaryColor(): string
    {
        return (string) config('crm.ui.primary_color', '#2563eb');
    }
}
