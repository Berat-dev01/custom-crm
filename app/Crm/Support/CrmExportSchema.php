<?php

namespace App\Crm\Support;

class CrmExportSchema
{
    /**
     * @return list<array{key: string, label: string, default: bool}>
     */
    public static function columns(string $module): array
    {
        return match ($module) {
            'contacts' => [
                ['key' => 'full_name',         'label' => 'Full Name',        'default' => true],
                ['key' => 'first_name',        'label' => 'First Name',       'default' => false],
                ['key' => 'last_name',         'label' => 'Last Name',        'default' => false],
                ['key' => 'email',             'label' => 'Email',            'default' => true],
                ['key' => 'phone',             'label' => 'Phone',            'default' => true],
                ['key' => 'title',             'label' => 'Title',            'default' => false],
                ['key' => 'company',           'label' => 'Company',          'default' => true],
                ['key' => 'lifecycle_stage',   'label' => 'Lifecycle Stage',  'default' => true],
                ['key' => 'source',            'label' => 'Source',           'default' => false],
                ['key' => 'owner',             'label' => 'Owner',            'default' => true],
                ['key' => 'tags',              'label' => 'Tags',             'default' => false],
                ['key' => 'last_contacted_at', 'label' => 'Last Contacted',   'default' => false],
            ],
            'companies' => [
                ['key' => 'name',           'label' => 'Name',           'default' => true],
                ['key' => 'email',          'label' => 'Email',          'default' => true],
                ['key' => 'phone',          'label' => 'Phone',          'default' => true],
                ['key' => 'website',        'label' => 'Website',        'default' => false],
                ['key' => 'tax_number',     'label' => 'Tax Number',     'default' => false],
                ['key' => 'tax_office',     'label' => 'Tax Office',     'default' => false],
                ['key' => 'sector',         'label' => 'Sector',         'default' => true],
                ['key' => 'city',           'label' => 'City',           'default' => false],
                ['key' => 'country',        'label' => 'Country',        'default' => false],
                ['key' => 'owner',          'label' => 'Owner',          'default' => true],
                ['key' => 'tags',           'label' => 'Tags',           'default' => false],
                ['key' => 'contacts_count', 'label' => 'Contacts',       'default' => false],
                ['key' => 'deals_count',    'label' => 'Deals',          'default' => false],
                ['key' => 'quotes_count',   'label' => 'Quotes',         'default' => false],
            ],
            'deals' => [
                ['key' => 'title',               'label' => 'Title',               'default' => true],
                ['key' => 'company',             'label' => 'Company',             'default' => true],
                ['key' => 'contact',             'label' => 'Contact',             'default' => false],
                ['key' => 'stage',               'label' => 'Stage',               'default' => true],
                ['key' => 'status',              'label' => 'Status',              'default' => true],
                ['key' => 'value',               'label' => 'Value',               'default' => true],
                ['key' => 'currency',            'label' => 'Currency',            'default' => false],
                ['key' => 'probability',         'label' => 'Probability',         'default' => false],
                ['key' => 'expected_close_date', 'label' => 'Expected Close Date', 'default' => true],
                ['key' => 'owner',               'label' => 'Owner',               'default' => true],
                ['key' => 'tags',                'label' => 'Tags',                'default' => false],
                ['key' => 'lost_reason',         'label' => 'Lost Reason',         'default' => false],
            ],
            'quotes' => [
                ['key' => 'quote_number',   'label' => 'Quote Number',   'default' => true],
                ['key' => 'company',        'label' => 'Company',        'default' => true],
                ['key' => 'contact',        'label' => 'Contact',        'default' => false],
                ['key' => 'deal',           'label' => 'Deal',           'default' => false],
                ['key' => 'status',         'label' => 'Status',         'default' => true],
                ['key' => 'currency',       'label' => 'Currency',       'default' => false],
                ['key' => 'subtotal',       'label' => 'Subtotal',       'default' => false],
                ['key' => 'discount_total', 'label' => 'Discount',       'default' => false],
                ['key' => 'tax_total',      'label' => 'Tax',            'default' => false],
                ['key' => 'grand_total',    'label' => 'Grand Total',    'default' => true],
                ['key' => 'valid_until',    'label' => 'Valid Until',    'default' => true],
                ['key' => 'owner',          'label' => 'Owner',          'default' => true],
                ['key' => 'tags',           'label' => 'Tags',           'default' => false],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function formats(string $module): array
    {
        return match ($module) {
            'contacts', 'companies', 'deals', 'quotes' => ['csv', 'excel'],
            default => ['csv'],
        };
    }
}
