@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage    = $paginator->lastPage();
        $total       = 7;

        if ($lastPage <= $total) {
            $start    = 1;
            $end      = $lastPage;
            $leftDot  = false;
            $rightDot = false;
        } else {
            $half = (int) floor($total / 2);

            if ($currentPage <= $half + 1) {
                $start    = 1;
                $end      = $total - 1;
                $leftDot  = false;
                $rightDot = true;
            } elseif ($currentPage >= $lastPage - $half) {
                $start    = $lastPage - ($total - 2);
                $end      = $lastPage;
                $leftDot  = true;
                $rightDot = false;
            } else {
                $start    = $currentPage - 2;
                $end      = $currentPage + 2;
                $leftDot  = true;
                $rightDot = true;
            }
        }

        $btnBase     = "relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium border leading-5 transition ease-in-out duration-150 focus:z-10 focus:outline-none focus:ring-1 focus:ring-blue-400";
        $btnActive   = "text-white bg-blue-600 border-blue-600 cursor-default font-semibold";
        $btnInactive = "text-gray-600 bg-white border-gray-200 hover:bg-blue-50 hover:text-blue-600 active:bg-gray-100";
        $btnDots     = "text-gray-400 bg-white border-gray-200 cursor-default";
        $btnNav      = "relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium border border-gray-200 leading-5 transition ease-in-out duration-150 focus:z-10 focus:outline-none focus:ring-1 focus:ring-blue-400";
        $btnNavOn    = "text-gray-500 bg-white hover:bg-gray-50 hover:text-gray-700 active:bg-gray-100";
        $btnNavOff   = "text-gray-300 bg-white cursor-default";
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center gap-2">

        {{-- Mobile --}}
        <div class="flex justify-between flex-1 w-full sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-default leading-5 rounded-md">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 leading-5 rounded-md hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-400 transition ease-in-out duration-150">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-600 bg-white border border-gray-200 leading-5 rounded-md hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-400 transition ease-in-out duration-150">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-default leading-5 rounded-md">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:items-center sm:justify-center">
            <span class="relative z-0 inline-flex rtl:flex-row-reverse shadow-sm rounded-md">

                {{-- << First --}}
                @if ($paginator->onFirstPage())
                    <span class="{{ $btnNav }} {{ $btnNavOff }} rounded-l-md" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M15.707 5.293a1 1 0 010 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 0zM9.707 5.293a1 1 0 010 1.414L5.414 10l4.293 4.293a1 1 0 01-1.414 1.414l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->url(1) }}" aria-label="Go to first page" class="{{ $btnNav }} {{ $btnNavOn }} rounded-l-md">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M15.707 5.293a1 1 0 010 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 0zM9.707 5.293a1 1 0 010 1.414L5.414 10l4.293 4.293a1 1 0 01-1.414 1.414l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                {{-- < Prev --}}
                @if ($paginator->onFirstPage())
                    <span class="{{ $btnNav }} {{ $btnNavOff }}" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="{{ $btnNav }} {{ $btnNavOn }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                {{-- Left dots --}}
                @if ($leftDot)
                    <span class="{{ $btnBase }} {{ $btnDots }}">...</span>
                @endif

                {{-- Page window --}}
                @for ($page = $start; $page <= $end; $page++)
                    @if ($page === $currentPage)
                        <span aria-current="page" class="{{ $btnBase }} {{ $btnActive }}">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}"
                           aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                           class="{{ $btnBase }} {{ $btnInactive }}">{{ $page }}</a>
                    @endif
                @endfor

                {{-- Right dots --}}
                @if ($rightDot)
                    <span class="{{ $btnBase }} {{ $btnDots }}">...</span>
                @endif

                {{-- > Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="{{ $btnNav }} {{ $btnNavOn }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="{{ $btnNav }} {{ $btnNavOff }}" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif

                {{-- >> Last --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->url($lastPage) }}" aria-label="Go to last page" class="{{ $btnNav }} {{ $btnNavOn }} rounded-r-md">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 14.707a1 1 0 010-1.414L8.586 10 4.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0zM10.293 14.707a1 1 0 010-1.414L14.586 10l-4.293-4.293a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="{{ $btnNav }} {{ $btnNavOff }} rounded-r-md" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 14.707a1 1 0 010-1.414L8.586 10 4.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0zM10.293 14.707a1 1 0 010-1.414L14.586 10l-4.293-4.293a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif

            </span>
        </div>

        {{-- Showing info --}}
        <p class="text-xs text-gray-400">
            Showing
            @if ($paginator->firstItem())
                <span class="font-medium text-gray-500">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-medium text-gray-500">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            of
            <span class="font-medium text-gray-500">{{ $paginator->total() }}</span>
            results
        </p>

    </nav>
@endif