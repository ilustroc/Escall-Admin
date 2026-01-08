@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        
        {{-- Versión Móvil (Anterior / Siguiente simple) --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-200 cursor-default rounded-md leading-5">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-md leading-5 hover:text-slate-800 focus:outline-none focus:ring ring-blue-300 active:bg-slate-100 transition ease-in-out duration-150">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-md leading-5 hover:text-slate-800 focus:outline-none focus:ring ring-blue-300 active:bg-slate-100 transition ease-in-out duration-150">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-400 bg-white border border-slate-200 cursor-default rounded-md leading-5">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        {{-- Versión Escritorio --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            
            {{-- Texto de información (Mostrando 1 a 10 de 50) --}}
            <div>
                <p class="text-xs text-slate-500">
                    Mostrando
                    <span class="font-bold text-slate-700">{{ $paginator->firstItem() }}</span>
                    a
                    <span class="font-bold text-slate-700">{{ $paginator->lastItem() }}</span>
                    de
                    <span class="font-bold text-slate-700">{{ $paginator->total() }}</span>
                    resultados
                </p>
            </div>

            {{-- Botonera --}}
            <div>
                <span class="relative z-0 inline-flex shadow-sm rounded-md">
                    
                    {{-- Botón Atrás --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-300 bg-white border border-slate-200 cursor-default rounded-l-md leading-5" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-l-md leading-5 hover:bg-slate-50 hover:text-slate-700 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:bg-slate-100 transition ease-in-out duration-150" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Números de Página --}}
                    @foreach ($elements as $element)
                        
                        {{-- Separador "..." --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-200 cursor-default leading-5 border-l-0">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    {{-- PAGINA ACTIVA: Azul (o Slate-800 si prefieres negro) --}}
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-white bg-blue-600 border border-blue-600 cursor-default leading-5 z-10 -ml-px">{{ $page }}</span>
                                    </span>
                                @else
                                    {{-- PAGINA INACTIVA: Blanco --}}
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 leading-5 hover:bg-slate-50 hover:text-slate-800 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:bg-slate-100 transition ease-in-out duration-150 -ml-px" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Botón Siguiente --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-r-md leading-5 hover:bg-slate-50 hover:text-slate-700 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:bg-slate-100 transition ease-in-out duration-150 -ml-px" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-300 bg-white border border-slate-200 cursor-default rounded-r-md leading-5 -ml-px" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif