<?php

namespace App\Crm\Services\Navigation;

use App\Crm\Support\CrmLabelCatalog;
use Illuminate\Http\Request;

class CrmNavigation
{
    public function __construct(private readonly CrmLabelCatalog $labels) {}

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
        return $this->labels->navigationGroups();
    }
}
