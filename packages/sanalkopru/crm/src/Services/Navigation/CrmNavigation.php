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
     * @return list<array{label: string, icon: string, active: bool, items: list<array{label: string, route: string, permission: string, active: bool}>}>
     */
    public function groups(Request $request): array
    {
        return collect($this->groupDefinitions())
            ->map(function (array $group) use ($request): array {
                $items = collect($group['items'])
                    ->map(fn (array $item): array => [
                        ...$item,
                        'active' => $request->routeIs($item['route']) || $request->routeIs($item['route'].'.*'),
                    ])
                    ->all();

                return [
                    ...$group,
                    'items' => $items,
                    'active' => collect($items)->contains(fn (array $item): bool => $item['active']),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, route: string, permission: string}>
     */
    private function definitions(): array
    {
        return collect($this->groupDefinitions())
            ->flatMap(fn (array $group): array => $group['items'])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, icon: string, items: list<array{label: string, route: string, permission: string}>}>
     */
    private function groupDefinitions(): array
    {
        return [
            [
                'label' => 'Overview',
                'icon' => 'layout-dashboard',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'crm.dashboard', 'permission' => 'crm.dashboard.view'],
                    ['label' => 'Search', 'route' => 'crm.search', 'permission' => 'crm.dashboard.view'],
                ],
            ],
            [
                'label' => 'Sales',
                'icon' => 'badge-dollar-sign',
                'items' => [
                    ['label' => 'Deals', 'route' => 'crm.deals.index', 'permission' => 'crm.deals.view'],
                    ['label' => 'Quotes', 'route' => 'crm.quotes.index', 'permission' => 'crm.quotes.view'],
                    ['label' => 'Deal Stages', 'route' => 'crm.deal-stages.index', 'permission' => 'crm.settings.manage'],
                ],
            ],
            [
                'label' => 'Customers',
                'icon' => 'users',
                'items' => [
                    ['label' => 'Contacts', 'route' => 'crm.contacts.index', 'permission' => 'crm.contacts.view'],
                    ['label' => 'Companies', 'route' => 'crm.companies.index', 'permission' => 'crm.companies.view'],
                ],
            ],
            [
                'label' => 'Operations',
                'icon' => 'clipboard-check',
                'items' => [
                    ['label' => 'Tasks', 'route' => 'crm.tasks.index', 'permission' => 'crm.tasks.view'],
                    ['label' => 'Activities', 'route' => 'crm.activities.index', 'permission' => 'crm.activities.view'],
                    ['label' => 'Tags', 'route' => 'crm.tags.index', 'permission' => 'crm.tags.view'],
                ],
            ],
            [
                'label' => 'System',
                'icon' => 'settings',
                'items' => [
                    ['label' => 'Users', 'route' => 'crm.users.index', 'permission' => 'crm.users.manage'],
                    ['label' => 'Settings', 'route' => 'crm.settings.index', 'permission' => 'crm.settings.manage'],
                ],
            ],
        ];
    }
}
