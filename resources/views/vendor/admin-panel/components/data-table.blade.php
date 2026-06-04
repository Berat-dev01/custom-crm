@props([
    'endpoint',
    'columns' => [],
    'filters' => [],
    'search' => true,
    'export' => false,
    'buttons' => [],
    'perPage' => config('admin-panel.pagination', 20),
    'emptyIcon' => 'inbox',
    'emptyMessage' => 'No data found',
])

<x-admin-panel::listing
    :endpoint="$endpoint"
    :columns="$columns"
    :filters="$filters"
    :search="$search"
    :export="$export"
    :buttons="$buttons"
    :per-page="$perPage"
    :empty-icon="$emptyIcon"
    :empty-message="$emptyMessage"
    {{ $attributes }}
/>
