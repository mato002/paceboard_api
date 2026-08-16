@if ($paginator->hasPages())
<nav class="pagination-nav" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
        <span class="pagination-btn disabled"><i class="fa-solid fa-chevron-left"></i></span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn" data-turbo-frame="main-content" data-turbo-action="advance" rel="prev">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
    @endif

    <div class="pagination-pages">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-ellipsis">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-page active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination-page" data-turbo-frame="main-content" data-turbo-action="advance">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn" data-turbo-frame="main-content" data-turbo-action="advance" rel="next">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    @else
        <span class="pagination-btn disabled"><i class="fa-solid fa-chevron-right"></i></span>
    @endif
</nav>
@endif
