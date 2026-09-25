@if ($paginator->hasPages())
    <nav class="no-print flex items-center justify-between gap-3 px-4 py-3 text-sm" aria-label="Pagination">
        <p class="text-slate-500">
            @if (method_exists($paginator, 'total'))
                {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }}
            @else
                Page {{ $paginator->currentPage() }}
            @endif
        </p>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="btn-secondaire btn-petit opacity-50">‹ Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn-secondaire btn-petit" rel="prev">‹ Précédent</a>
            @endif
            @if (method_exists($paginator, 'lastPage'))
                <span class="text-slate-500">Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn-secondaire btn-petit" rel="next">Suivant ›</a>
            @else
                <span class="btn-secondaire btn-petit opacity-50">Suivant ›</span>
            @endif
        </div>
    </nav>
@endif
