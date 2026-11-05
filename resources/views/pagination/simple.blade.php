@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang">
        @if ($paginator->onFirstPage())
            <button type="button" class="btn" disabled>Trước</button>
        @else
            <a class="btn" href="{{ $paginator->previousPageUrl() }}">Trước</a>
        @endif
        <span>Trang {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn" href="{{ $paginator->nextPageUrl() }}">Sau</a>
        @else
            <button type="button" class="btn" disabled>Sau</button>
        @endif
    </nav>
@endif
