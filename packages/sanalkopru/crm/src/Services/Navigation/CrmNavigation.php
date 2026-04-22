<?php

namespace Sanalkopru\Crm\Services\Navigation;

use Illuminate\Http\Request;

class CrmNavigation
{
    /**
     * @return list<array{label: string, route: string, permission: string, active: bool}>
     */
    public function items(Request $request): array
    {
        return collect($this->definitions())
            ->map(fn (array $item): array => [
                ...$item,
                'active' => $request->routeIs($item['route']) || $request->routeIs($item['route'].'.*'),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, route: string, permission: string}>
     */
    private function definitions(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'crm.dashboard', 'permission' => 'crm.dashboard.view'],
            ['label' => 'Contacts', 'route' => 'crm.contacts.index', 'permission' => 'crm.contacts.view'],
            ['label' => 'Companies', 'route' => 'crm.companies.index', 'permission' => 'crm.companies.view'],
            ['label' => 'Deals', 'route' => 'crm.deals.index', 'permission' => 'crm.deals.view'],
            ['label' => 'Tasks', 'route' => 'crm.tasks.index', 'permission' => 'crm.tasks.view'],
            ['label' => 'Quotes', 'route' => 'crm.quotes.index', 'permission' => 'crm.quotes.view'],
            ['label' => 'Activities', 'route' => 'crm.activities.index', 'permission' => 'crm.activities.view'],
            ['label' => 'Tags', 'route' => 'crm.tags.index', 'permission' => 'crm.tags.view'],
        ];
    }
}
