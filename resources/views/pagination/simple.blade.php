@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang">
        @if ($paginator->onFirstPage())
            <button type="button" class="btn btn--sm" disabled>Trang trước</button>
        @else
            <a class="btn btn--sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">Trang trước</a>
        @endif
        <span>Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn--sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Trang sau</a>
        @else
            <button type="button" class="btn btn--sm" disabled>Trang sau</button>
        @endif
    </nav>
@endif
