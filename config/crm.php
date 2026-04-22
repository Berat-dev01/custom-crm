<?php

return [
    'routes' => [
        'admin_prefix' => env('CRM_ROUTE_PREFIX', 'admin/crm'),
        'middleware' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRM_ROUTE_MIDDLEWARE', 'web'))
        ))),
    ],

    'tenancy' => [
        'mode' => env('CRM_TENANCY_MODE', 'single'),
        'default_organization_name' => env('CRM_DEFAULT_ORGANIZATION_NAME', 'Default Organization'),
    ],

    'modules' => [
        'contacts' => env('CRM_MODULE_CONTACTS', true),
        'companies' => env('CRM_MODULE_COMPANIES', true),
        'deals' => env('CRM_MODULE_DEALS', true),
        'tasks' => env('CRM_MODULE_TASKS', true),
        'quotes' => env('CRM_MODULE_QUOTES', true),
        'activities' => env('CRM_MODULE_ACTIVITIES', true),
        'ai' => env('CRM_MODULE_AI', true),
    ],

    'money' => [
        'default_currency' => env('CRM_CURRENCY', 'TRY'),
        'supported_currencies' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRM_SUPPORTED_CURRENCIES', 'TRY,USD,EUR'))
        ))),
        'default_tax_rate' => (float) env('CRM_TAX_RATE', 20),
    ],

    'quotes' => [
        'number_prefix' => env('CRM_QUOTE_NUMBER_PREFIX', 'CRM-'),
        'number_padding' => (int) env('CRM_QUOTE_NUMBER_PADDING', 6),
    ],

    'ai' => [
        'enabled' => env('CRM_AI_ENABLED', false),
        'driver' => env('CRM_AI_DRIVER', env('CRM_AI_PROVIDER', 'openai')),
        'provider' => env('CRM_AI_PROVIDER', env('CRM_AI_DRIVER', 'openai')),
        'model' => env('CRM_AI_MODEL'),
        'max_tokens' => (int) env('CRM_AI_MAX_TOKENS', 1200),
        'temperature' => (float) env('CRM_AI_TEMPERATURE', 0.3),
        'drivers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'organization' => env('OPENAI_ORGANIZATION'),
                'project' => env('OPENAI_PROJECT'),
                'base_url' => env('OPENAI_BASE_URL'),
                'model' => env('OPENAI_MODEL', env('CRM_AI_MODEL')),
                'request_timeout' => (int) env('OPENAI_REQUEST_TIMEOUT', 30),
            ],
            'claude' => [
                'api_key' => env('CLAUDE_API_KEY'),
                'base_url' => env('CLAUDE_BASE_URL'),
                'model' => env('CLAUDE_MODEL', env('CRM_AI_MODEL')),
                'request_timeout' => (int) env('CLAUDE_REQUEST_TIMEOUT', 30),
            ],
            'gemini' => [
                'api_key' => env('GEMINI_API_KEY'),
                'base_url' => env('GEMINI_BASE_URL'),
                'model' => env('GEMINI_MODEL', env('CRM_AI_MODEL')),
                'request_timeout' => (int) env('GEMINI_REQUEST_TIMEOUT', 30),
            ],
            'null' => [
                'model' => null,
                'request_timeout' => 0,
            ],
        ],
    ],

    'notifications' => [
        'task_reminders' => env('CRM_NOTIFY_TASK_REMINDERS', true),
        'quote_status_changes' => env('CRM_NOTIFY_QUOTE_STATUS_CHANGES', true),
    ],

    'permissions' => [
        'enabled' => env('CRM_PERMISSIONS_ENABLED', true),
        'roles' => [
            'owner',
            'manager',
            'sales',
            'support',
        ],
    ],

    'ui' => [
        'app_name' => env('CRM_UI_APP_NAME', env('APP_NAME', 'CRM Engine')),
        'primary_color' => env('CRM_UI_PRIMARY_COLOR', '#2563eb'),
    ],
];
