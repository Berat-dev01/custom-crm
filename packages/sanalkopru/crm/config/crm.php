<?php

return [
    'routes' => [
        'admin_prefix' => env('CRM_ROUTE_PREFIX', 'admin/crm'),
        'middleware' => ['web'],
    ],

    'tenancy' => [
        'mode' => env('CRM_TENANCY_MODE', 'single'),
        'default_organization_name' => env('CRM_DEFAULT_ORGANIZATION_NAME', 'Default Organization'),
    ],

    'money' => [
        'currency' => env('CRM_CURRENCY', 'TRY'),
        'tax_rate' => (float) env('CRM_TAX_RATE', 20),
    ],

    'ai' => [
        'enabled' => env('CRM_AI_ENABLED', false),
        'driver' => env('CRM_AI_DRIVER', 'openai'),
        'model' => env('CRM_AI_MODEL'),
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
];
