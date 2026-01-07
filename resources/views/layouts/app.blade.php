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
        /* Scrollbar personalizada para el sidebar */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none;  scrollbar-width: none; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased text-slate-600">

    <div class="flex h-screen overflow-hidden">

        {{-- SIDEBAR (Escritorio y Móvil) --}}
        <div id="mobile-overlay" class="fixed inset-0 z-20 bg-black/50 transition-opacity opacity-0 pointer-events-none lg:hidden" aria-hidden="true"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-slate-900 text-white transition-transform duration-300 -translate-x-full lg:static lg:translate-x-0">
            
            {{-- Logo --}}
            <div class="flex h-16 items-center justify-center border-b border-slate-800 bg-slate-950 px-6">
                <a href="{{ route('dashboard') }}">
                    {{-- Aquí llamamos a la imagen --}}
                    <img src="{{ asset('img/logotipo-escallperu.png') }}" 
                        alt="Escall Perú" 
                        class="h-10 w-auto object-contain"> 
                </a>
            </div>
            
            {{-- Menú de Navegación --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                
                {{-- Grupo: Principal --}}
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Principal</p>
                    <div class="space-y-1">
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            Dashboard
                        </a>
                    </div>
                </div>

                {{-- Grupo: Operativo --}}
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Operativo</p>
                    <div class="space-y-1">
                        
                        {{-- ACORDEÓN CARGAS --}}
                        <div x-data="{ open: {{ request()->routeIs('cargas.*') ? 'true' : 'false' }} }">
                            
                            {{-- Botón Principal del Acordeón --}}
                            <button @click="open = !open" 
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors 
                                    {{ request()->routeIs('cargas.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                
                                <div class="flex items-center gap-3">
                                    {{-- Icono Upload --}}
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <span>Cargas</span>
                                </div>

                                {{-- Flecha Rotatoria --}}
                                <svg class="h-4 w-4 transition-transform duration-200" 
                                    :class="open ? 'rotate-90' : ''" 
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            {{-- Sub-menú (Items) --}}
                            <div x-show="open" 
                                x-cloak 
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                class="mt-1 space-y-1 pl-11">
                                
                                {{-- Item: Gestiones --}}
                                <a href="{{ route('cargas.index', ['tab' => 'gestiones']) }}" 
                                class="block rounded-lg px-3 py-2 text-xs font-medium transition-colors 
                                {{ request('tab') == 'gestiones' || !request('tab') && request()->routeIs('cargas.index') ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Gestiones
                                </a>

                                {{-- Item: Cartera --}}
                                <a href="{{ route('cargas.index', ['tab' => 'data']) }}" 
                                class="block rounded-lg px-3 py-2 text-xs font-medium transition-colors 
                                {{ request('tab') == 'data' ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Carga Cartera
                                </a>

                                {{-- Item: Pagos --}}
                                <a href="{{ route('cargas.index', ['tab' => 'pagos']) }}" 
                                class="block rounded-lg px-3 py-2 text-xs font-medium transition-colors 
                                {{ request('tab') == 'pagos' ? 'text-white bg-blue-600' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    Carga Pagos
                                </a>
                            </div>
                        </div>
                        {{-- FIN ACORDEÓN --}}

                        {{-- Item: Mensajería SMS (Se mantiene igual) --}}
                        <a href="{{ route('sms.index') }}" 
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('sms.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                            Mensajería SMS
                        </a>

                    </div>
                </div>

                {{-- Grupo: Analítica --}}
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Analítica</p>
                    <div class="space-y-1">
                        <a href="{{ route('tablas.index') }}" 
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('tablas.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                           <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            Tablas
                        </a>
                        <a href="{{ route('reportes.index') }}" 
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('reportes.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                           <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Reportes
                        </a>
                    </div>
                </div>

            </nav>

            {{-- Footer Sidebar --}}
            <div class="border-t border-slate-800 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
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
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
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

            {{-- Area de Contenido Scrollable --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-8">
                
                {{-- Alertas / Flash Messages --}}
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

                {{-- Inyección de Contenido --}}
                <div class="animate-fade-in-up">
                    @yield('content')
                </div>

            </main>
        </div>
    </div>

    {{-- Script para menú móvil --}}
    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');

        function toggleMenu() {
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        btn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    </script>
</body>
</html>