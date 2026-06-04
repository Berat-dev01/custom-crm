@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="pagination-summary">
            {{ __('Page') }}
            <strong>{{ $paginator->currentPage() }}</strong>
        </p>

        <div class="pagination">
            @if ($paginator->onFirstPage())
                <span class="disabled" aria-disabled="true">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">
                    {{ __('pagination.previous') }}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">
                    {{ __('pagination.next') }}
                </a>
            @else
                <span class="disabled" aria-disabled="true">{{ __('pagination.next') }}</span>
            @endif
        </div>
    </nav>
@endif
