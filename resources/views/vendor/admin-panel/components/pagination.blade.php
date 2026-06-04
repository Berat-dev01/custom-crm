@props([
    'paginator' => null,
    'compact' => true,
    'perPageOptions' => [10 => '10', 25 => '25', 50 => '50', 100 => '100'],
    'perPageLabel' => 'Rows',
])

@if($paginator)
    @php
        $currentPerPage = (string) request()->integer('per_page', method_exists($paginator, 'perPage') ? $paginator->perPage() : 25);
        $normalizedPerPageOptions = collect($perPageOptions)
            ->mapWithKeys(fn ($label, $value) => [(string) $value => (string) $label])
            ->all();

        if (! array_key_exists($currentPerPage, $normalizedPerPageOptions)) {
            $normalizedPerPageOptions[$currentPerPage] = $currentPerPage;
            ksort($normalizedPerPageOptions, SORT_NUMERIC);
        }

        $hiddenInputs = [];
        $appendQueryInput = function (string $name, mixed $value) use (&$hiddenInputs, &$appendQueryInput): void {
            if (is_array($value)) {
                foreach ($value as $nestedKey => $nestedValue) {
                    $appendQueryInput($name.'['.$nestedKey.']', $nestedValue);
                }

                return;
            }

            if ($value === null || $value === '') {
                return;
            }

            $hiddenInputs[] = [
                'name' => $name,
                'value' => (string) $value,
            ];
        };

        foreach (request()->except(['page', 'per_page']) as $queryKey => $queryValue) {
            $appendQueryInput($queryKey, $queryValue);
        }

        $firstItem = $paginator->count() > 0 ? $paginator->firstItem() : 0;
        $lastItem = $paginator->count() > 0 ? $paginator->lastItem() : 0;
        $currentPage = method_exists($paginator, 'currentPage') ? $paginator->currentPage() : 1;
        $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : 1;
    @endphp

    <div {{ $attributes->class([
        'pagination-wrapper',
        'pagination-wrapper-compact' => $compact,
    ]) }} data-export-total="{{ $paginator->total() }}">
        <div class="pagination-toolbar">
            <div class="pagination-toolbar-primary">
                <div class="pagination-summary">
                    <span class="pagination-range">
                        <strong>{{ $firstItem }}-{{ $lastItem }}</strong>
                        <span>/</span>
                        <strong>{{ $paginator->total() }}</strong>
                    </span>
                    <span class="pagination-page-indicator">
                        Page {{ $currentPage }}/{{ $lastPage }}
                    </span>
                </div>
            </div>

            <div class="pagination-toolbar-secondary">
                <div class="pagination-per-page-form" data-admin-ajax-pagination-form data-action="{{ request()->url() }}">
                    @foreach($hiddenInputs as $input)
                        <input type="hidden" name="{{ $input['name'] }}" value="{{ $input['value'] }}">
                    @endforeach

                    <div class="pagination-per-page-inline">
                        <span class="pagination-per-page-label">{{ $perPageLabel }}</span>
                        <x-admin-panel::select
                            name="per_page"
                            :options="$normalizedPerPageOptions"
                            :selected="$currentPerPage"
                            size="sm"
                            :searchable="false"
                            :clearable="false"
                            :custom="true"
                            group-class="pagination-per-page-field"
                        />
                    </div>
                </div>

                @if ($paginator->hasPages())
                    <nav class="pagination-nav" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
                        {{ $paginator->links('admin-panel::pagination.links') }}
                    </nav>
                @else
                    <div class="pagination-nav" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
                        <div class="pagination">
                            <span class="active" aria-current="page">1</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@else
    <div
        data-listing-pagination
        {{ $attributes->class([
            'pagination-wrapper',
            'pagination-wrapper-compact' => $compact,
        ]) }}
    >
        {{ $slot }}
    </div>
@endif
