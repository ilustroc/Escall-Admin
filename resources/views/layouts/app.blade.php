<!doctype html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'ESCALL • Software')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        /* === SCROLLBAR MODERNA === */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased text-slate-600">

    <div class="flex h-screen overflow-hidden">

        {{-- SIDEBAR --}}
        <div id="mobile-overlay" class="fixed inset-0 z-20 bg-black/50 transition-opacity opacity-0 pointer-events-none lg:hidden" aria-hidden="true"></div>
        
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 flex w-60 flex-col bg-slate-900 text-white transition-transform duration-300 -translate-x-full lg:static lg:translate-x-0">
            
            {{-- Logo --}}
            <div class="flex h-16 items-center justify-center border-b border-slate-800 bg-slate-950 px-4">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('img/logotipo-escallperu.png') }}" alt="Escall Perú" class="h-9 w-auto object-contain"> 
                </a>
            </div>
            
            <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-4">
                
                {{-- GRUPO: PRINCIPAL --}}
                <div>
                    <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Principal</p>
                    <div class="space-y-0.5">
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <x-heroicon-o-home class="h-5 w-5"/>
                            Dashboard
                        </a>
                    </div>
                </div>

                {{-- GRUPO: OPERATIVO --}}
                <div>
                    <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Operativo</p>
                    <div class="space-y-0.5">
                        
                        {{-- 1. ACORDEÓN CARGAS --}}
                        <div x-data="{ open: {{ request()->routeIs('cargas.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-sm font-medium transition-colors 
                                    {{ request()->routeIs('cargas.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <div class="flex items-center gap-2.5">
                                    <x-heroicon-o-cloud-arrow-up class="h-5 w-5"/>
                                    <span>Cargas</span>
                                </div>
                                <span class="h-4 w-4 transition-transform duration-200"
                                    :class="open ? 'rotate-90' : ''">
                                    <x-heroicon-o-chevron-right class="h-4 w-4"/>
                                </span>
                            </button>

                            <div x-show="open" x-cloak class="mt-0.5 space-y-0.5 pl-9">
                                <a href="{{ route('cargas.index', ['tab' => 'gestiones']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('cargas.index') && (request('tab') == 'gestiones' || !request('tab')) ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Gestiones
                                </a>
                                <a href="{{ route('cargas.index', ['tab' => 'data']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('cargas.index') && request('tab') == 'data' ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Carga Cartera
                                </a>
                                <a href="{{ route('cargas.index', ['tab' => 'pagos']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('cargas.index') && request('tab') == 'pagos' ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Carga Pagos
                                </a>
                            </div>
                        </div>

                        {{-- 2. ACORDEÓN LISTAS Y REGISTROS (NUEVO) --}}
                        <div x-data="{ open: {{ request()->routeIs('listas.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-sm font-medium transition-colors 
                                    {{ request()->routeIs('listas.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <div class="flex items-center gap-2.5">
                                    <x-heroicon-o-queue-list class="h-5 w-5"/>
                                    <span>Listas</span>
                                </div>
                                <span class="h-4 w-4 transition-transform duration-200"
                                    :class="open ? 'rotate-90' : ''">
                                    <x-heroicon-o-chevron-right class="h-4 w-4"/>
                                </span>                            
                            </button>

                            <div x-show="open" x-cloak class="mt-0.5 space-y-0.5 pl-9">
                                <a href="{{ route('listas.index', ['tab'=>'pagos']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('listas.index') && (request('tab') == 'pagos' || !request('tab')) ? 'text-white bg-emerald-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Pagos
                                </a>
                                <a href="{{ route('listas.index', ['tab'=>'gestiones']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('listas.index') && request('tab') == 'gestiones' ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Gestiones
                                </a>
                                <a href="{{ route('listas.index', ['tab'=>'data']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('listas.index') && request('tab') == 'data' ? 'text-white bg-purple-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Data Cartera
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- GRUPO: ANALÍTICA --}}
                <div>
                    <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Analítica</p>
                    <div class="space-y-0.5">
                        
                        {{-- 3. ACORDEÓN TABLAS --}}
                        <div x-data="{ open: {{ request()->routeIs('tablas.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-sm font-medium transition-colors 
                                    {{ request()->routeIs('tablas.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <div class="flex items-center gap-2.5">
                                    <x-heroicon-o-table-cells class="h-5 w-5"/>
                                    <span>Tablas</span>
                                </div>
                                <span class="h-4 w-4 transition-transform duration-200"
                                    :class="open ? 'rotate-90' : ''">
                                    <x-heroicon-o-chevron-right class="h-4 w-4"/>
                                </span>                            
                            </button>

                            <div x-show="open" x-cloak class="mt-0.5 space-y-0.5 pl-9">
                                <a href="{{ route('tablas.index', ['tab'=>'gestiones']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('tablas.index') && (request('tab') == 'gestiones' || !request('tab')) ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Gestiones
                                </a>
                                <a href="{{ route('tablas.index', ['tab'=>'pagos']) }}" 
                                   class="block rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('tablas.index') && request('tab') == 'pagos' ? 'text-white bg-emerald-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Pagos
                                </a>
                            </div>
                        </div>

                        {{-- Reportes --}}
                        <a href="{{ route('reportes.index') }}" 
                           class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('reportes.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <x-heroicon-o-document-chart-bar class="h-5 w-5"/>
                            Reportes
                        </a>
                    </div>
                </div>

            </nav>
            
            {{-- Footer Sidebar --}}
            <div class="border-t border-slate-800 p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition">
                        <x-heroicon-o-arrow-left-on-rectangle class="h-5 w-5"/>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="flex flex-1 flex-col overflow-hidden bg-slate-50">
            
            {{-- Header Superior --}}
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shadow-sm">
                <button id="mobile-menu-btn" class="text-slate-500 hover:text-slate-700 lg:hidden focus:outline-none">
                    <x-heroicon-o-bars-3 class="h-6 w-6"/>
                </button>

                <div class="flex flex-col">
                    <h1 class="text-lg font-bold text-slate-800 leading-tight">
                        @yield('header_title', 'Panel de Control')
                    </h1>
                    @hasSection('crumb')
                        <span class="text-xs text-slate-400">@yield('crumb')</span>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden text-right md:block">
                        <div class="text-sm font-semibold text-slate-800">{{ auth()->user()->name ?? 'Administrador' }}</div>
                        <div class="text-xs text-slate-500">{{ date('d M, Y') }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold border border-slate-300">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                </div>
            </header>

            {{-- Area de Contenido --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-5">
                
                {{-- Alertas --}}
                @if(session('ok') || session('warn') || session('error') || $errors->any())
                <div class="mb-6 space-y-3">
                    @if(session('ok'))
                        <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                            <span class="font-bold">✓ Éxito:</span> {{ session('ok') }}
                        </div>
                    @endif

                    @if(session('warn'))
                         <div class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm">
                            <span class="font-bold">⚠ Atención:</span> {{ session('warn') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                            <span class="font-bold">✕ Error:</span> {{ session('error') }}
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                @endif

                <div class="animate-fade-in-up">
                    @yield('content')
                </div>

            </main>
        </div>
    </div>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</body>
</html>