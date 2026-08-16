@if ($paginator->hasPages())
<nav class="pagination-nav" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
        <span class="pagination-btn disabled"><i class="fa-solid fa-chevron-left"></i></span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn" data-turbo-frame="main-content" data-turbo-action="advance" rel="prev">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
    @endif

    <span class="pagination-info">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn" data-turbo-frame="main-content" data-turbo-action="advance" rel="next">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    @else
        <span class="pagination-btn disabled"><i class="fa-solid fa-chevron-right"></i></span>
    @endif
</nav>
@endif
