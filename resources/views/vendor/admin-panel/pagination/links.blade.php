<div class="pagination">
    @if ($paginator->onFirstPage())
        <span class="pagination-arrow disabled" aria-disabled="true" title="{{ admin_trans('Previous page') }}">
            <svg viewBox="0 0 16 16" aria-hidden="true">
                <path d="M9.5 3.5L5 8l4.5 4.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
            </svg>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ admin_trans('Previous page') }}" class="pagination-arrow" title="{{ admin_trans('Previous page') }}">
            <svg viewBox="0 0 16 16" aria-hidden="true">
                <path d="M9.5 3.5L5 8l4.5 4.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
            </svg>
        </a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ admin_trans('Next page') }}" class="pagination-arrow" title="{{ admin_trans('Next page') }}">
            <svg viewBox="0 0 16 16" aria-hidden="true">
                <path d="M6.5 3.5L11 8l-4.5 4.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
            </svg>
        </a>
    @else
        <span class="pagination-arrow disabled" aria-disabled="true" title="{{ admin_trans('Next page') }}">
            <svg viewBox="0 0 16 16" aria-hidden="true">
                <path d="M6.5 3.5L11 8l-4.5 4.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
            </svg>
        </span>
    @endif
</div>
