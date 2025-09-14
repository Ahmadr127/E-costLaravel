@if ($paginator->hasPages())
<nav class="flex items-center justify-center px-4 py-3" aria-label="Pagination">
    <div class="flex flex-wrap items-center justify-center gap-1 max-w-full overflow-hidden">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-400 bg-white cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-left"></i>
            </a>
        @endif

        {{-- First Page --}}
        @if ($paginator->currentPage() > 3)
            <a href="{{ $paginator->url(1) }}" class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors">1</a>
            @if ($paginator->currentPage() > 4)
                <span class="px-2 py-1.5 text-sm text-gray-500">...</span>
            @endif
        @endif

        {{-- Pages Around Current Page --}}
        @php
            $start = max($paginator->currentPage() - 2, 1);
            $end = min($paginator->currentPage() + 2, $paginator->lastPage());
        @endphp

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $paginator->currentPage())
                <span class="inline-flex items-center px-2 py-1.5 text-sm rounded-md bg-green-600 text-white font-medium">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors">{{ $page }}</a>
            @endif
        @endfor

        {{-- Last Page --}}
        @if ($paginator->currentPage() < $paginator->lastPage() - 2)
            @if ($paginator->currentPage() < $paginator->lastPage() - 3)
                <span class="px-2 py-1.5 text-sm text-gray-500">...</span>
            @endif
            <a href="{{ $paginator->url($paginator->lastPage()) }}" class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors">{{ $paginator->lastPage() }}</a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-right"></i>
            </a>
        @else
            <span class="inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 text-gray-400 bg-white cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </span>
        @endif
    </div>
</nav>
@endif
