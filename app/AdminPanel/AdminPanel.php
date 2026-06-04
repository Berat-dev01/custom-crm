<?php

namespace App\AdminPanel;

class AdminPanel
{
    public function asset(string $path = ''): string
    {
        $base = trim(config('admin-panel.asset_path', 'vendor/admin-panel'), '/');

        return asset($base.($path !== '' ? '/'.ltrim($path, '/') : ''));
    }

    public function appName(): string
    {
        return (string) config('admin-panel.app_name', config('app.name', 'Admin Panel'));
    }

    public function logo(): ?string
    {
        return config('admin-panel.logo');
    }
}
