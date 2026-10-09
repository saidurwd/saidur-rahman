@if ($paginator->hasPages())
    <nav class="mt-14 flex items-center justify-between gap-4 border-t border-line pt-6" aria-label="Pagination">
        @if ($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-outline">← Newer</a>
        @else<span></span>@endif
        <p class="label">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>
        @if ($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-outline">Older →</a>
        @else<span></span>@endif
    </nav>
@endif
