<?php

/*
 * Project-specific admin-panel configuration.
 * This file is committed to THIS project's git repo, never to the admin-panel package.
 * Run `php artisan vendor:publish --tag=admin-panel-config` to regenerate defaults.
 */

return [
    'prefix' => 'admin',
    'middleware' => ['web', 'admin.auth'],
    'logo' => null,
    'app_name' => 'CRM',

    'theme' => [
        'primary' => '#3b82f6',
        'sidebar_bg' => '#0f172a',
    ],

    'pagination' => 20,
    'guard' => 'admin',
    'login_route' => 'admin.login',
    'asset_path' => 'vendor/admin-panel',

    /*
     * Role badge mapping for this project.
     * Format: 'role_name' => ['Display Label', 'badge-variant']
     * Available variants: primary, success, info, warning, danger, secondary
     */
    'roles' => [
        'crm_owner'   => ['Owner',   'primary'],
        'crm_manager' => ['Manager', 'success'],
        'crm_sales'   => ['Sales',   'info'],
        'crm_support' => ['Support', 'warning'],
        'crm_viewer'  => ['Viewer',  'secondary'],
        'superadmin'  => ['Superadmin', 'primary'],
    ],
    'default_role' => ['Staff', 'secondary'],
];
