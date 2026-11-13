@if ($paginator->hasPages())
<ul class="pagination">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <li class="page-item previous disabled">
            <span class="page-link"><i class="previous"></i></span>
        </li>
    @else
        <li class="page-item previous">
            <a href="{{ $paginator->previousPageUrl() }}" class="page-link" data-page="{{ $paginator->currentPage() - 1 }}">
                <i class="previous"></i>
            </a>
        </li>
    @endif

    {{-- Pagination Elements --}}
    @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
        @if ($page == $paginator->currentPage())
            <li class="page-item active">
                <span class="page-link">{{ $page }}</span>
            </li>
        @else
            <li class="page-item">
                <a href="{{ $url }}" class="page-link" data-page="{{ $page }}">{{ $page }}</a>
            </li>
        @endif
    @endforeach

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <li class="page-item next">
            <a href="{{ $paginator->nextPageUrl() }}" class="page-link" data-page="{{ $paginator->currentPage() + 1 }}">
                <i class="next"></i>
            </a>
        </li>
    @else
        <li class="page-item next disabled">
            <span class="page-link"><i class="next"></i></span>
        </li>
    @endif
</ul>
@endif