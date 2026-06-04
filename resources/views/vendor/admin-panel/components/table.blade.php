@props([
    'headers' => [],         // Array of table headers
    'searchUrl' => null,     // HTMX search endpoint
    'sortable' => false,
    'responsive' => true,
])

@php
    $tableId = $attributes->get('id', 'table-' . uniqid());
    $attributes = $attributes->class(['admin-table']);
@endphp

<div class="@if($responsive) overflow-x-auto @endif">
    <table {{ $attributes }} id="{{ $tableId }}">
        @if(count($headers) > 0)
            <thead>
                <tr>
                    @foreach($headers as $header)
                        @php
                            $sortableClass = $sortable && ($header['sortable'] ?? false) ? 'sortable' : '';
                            $width = $header['width'] ?? null;
                            $align = $header['align'] ?? 'left';
                        @endphp
                        <th
                            class="{{ $sortableClass }}"
                            @if($width) style="width: {{ $width }};" @endif
                            @if($sortable && ($header['sortable'] ?? false))
                                @click="sort('{{ $header['key'] ?? '' }}')"
                            @endif
                        >
                            <div class="d-flex items-center gap-2" style="text-align: {{ $align }};">
                                {{ $header['label'] ?? $header }}
                                @if($sortable && ($header['sortable'] ?? false))
                                    <i data-lucide="arrow-up-down" width="12" height="12"></i>
                                @endif
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody @if($searchUrl) id="{{ $tableId }}-body" @endif>
            {{ $slot }}
        </tbody>
    </table>

    <!-- Empty State -->
    @if(isset($empty))
        <div class="table-empty">
            {{ $empty }}
        </div>
    @endif
</div>

@if($searchUrl)
    <div
        hx-get="{{ $searchUrl }}"
        hx-trigger="search from:body"
        hx-target="#{{ $tableId }}-body"
        hx-swap="innerHTML"
        style="display: none;"
    ></div>
@endif
