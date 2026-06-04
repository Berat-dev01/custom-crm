@props([
    'endpoint',                  // JSON data endpoint (required)
    'columns' => [],             // Column definitions (required)
    'filters' => [],             // Filter definitions (optional)
    'search' => false,           // Enable search (optional)
    'export' => false,           // Enable CSV export (optional)
    'buttons' => [],             // Custom toolbar buttons (optional)
    'perPage' => 15,             // Items per page (optional)
    'emptyIcon' => 'inbox',      // Lucide icon for empty state
    'emptyMessage' => 'No data found',
])

@php
    $componentId = uniqid('listing_');
@endphp

<div id="{{ $componentId }}" class="admin-listing" data-endpoint="{{ $endpoint }}" data-per-page="{{ $perPage }}">
    {{-- Toolbar --}}
    <div class="listing-toolbar">
        <div class="listing-toolbar-left">
            @isset($toolbar)
                {{ $toolbar }}
            @else
                @foreach($buttons as $button)
                    <x-admin-panel::button
                        :variant="$button['class'] ?? 'primary'"
                        :href="$button['url'] ?? '#'"
                        :icon="$button['icon'] ?? null"
                    >
                        {{ admin_trans($button['label']) }}
                    </x-admin-panel::button>
                @endforeach
            @endisset
        </div>

        <div class="listing-toolbar-right">
            {{-- Search --}}
            @if($search)
                <div class="listing-search">
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="{{ admin_trans('Search...') }}"
                        data-listing-search
                    >
                </div>
            @endif

            {{-- Filters --}}
            @foreach($filters as $filter)
                <div class="listing-filter">
                    @if($filter['type'] === 'select')
                        <select class="form-control form-control-sm" name="{{ $filter['name'] }}" data-listing-filter>
                            <option value="">{{ admin_trans($filter['label']) }}</option>
                            @foreach($filter['options'] as $value => $label)
                                <option value="{{ $value }}">{{ admin_trans($label) }}</option>
                            @endforeach
                        </select>
                    @elseif($filter['type'] === 'date')
                        <input
                            type="date"
                            class="form-control form-control-sm"
                            name="{{ $filter['name'] }}"
                            placeholder="{{ admin_trans($filter['label']) }}"
                            data-listing-filter
                        >
                    @elseif($filter['type'] === 'text')
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            name="{{ $filter['name'] }}"
                            placeholder="{{ admin_trans($filter['label']) }}"
                            data-listing-filter
                        >
                    @endif
                </div>
            @endforeach

            {{-- Export --}}
            @if($export)
                <x-admin-panel::button
                    size="sm"
                    variant="outline"
                    icon="download"
                    data-listing-export
                >
                    {{ admin_trans('Export') }}
                </x-admin-panel::button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="listing-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th
                            @if($column['sortable'] ?? false)
                                class="sortable"
                                data-sort-key="{{ $column['key'] }}"
                                style="cursor: pointer; user-select: none;"
                            @endif
                            @if(isset($column['width']))
                                style="width: {{ $column['width'] }};"
                            @endif
                        >
                            {{ admin_trans($column['label']) }}
                            @if($column['sortable'] ?? false)
                                <span class="sort-indicator"></span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody data-listing-body>
                {{-- Rows will be populated by JavaScript --}}
                <tr>
                    <td colspan="{{ count($columns) }}" class="table-empty">
                        <div class="spinner"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="listing-pagination" data-listing-pagination>
        {{-- Pagination will be populated by JavaScript --}}
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset(config('admin-panel.asset_path', 'vendor/admin-panel') . '/js/listing.js') }}"></script>
    @endpush
@endonce

<script>
document.addEventListener('DOMContentLoaded', function() {
    const element = document.getElementById('{{ $componentId }}');
    if (element && window.AdminListing) {
        new window.AdminListing(element, {
            columns: @json($columns),
            emptyIcon: '{{ $emptyIcon }}',
            emptyMessage: @json(admin_trans($emptyMessage))
        });
    }
});
</script>
