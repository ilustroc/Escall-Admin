<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title','ESCALL • Software')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Tailwind CDN (sin Vite) --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">
    <div class="relative min-h-screen overflow-hidden flex flex-col">

        {{-- Fondo suave profesional --}}
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-indigo-200/35 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-slate-200/70 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(15,23,42,0.08)_1px,transparent_0)] [background-size:18px_18px] opacity-30"></div>
        </div>

        {{-- HEADER --}}
        <header class="relative sticky top-0 z-30 border-b border-slate-200 bg-white/85 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4">
                <div class="flex items-center justify-between h-14">
                    {{-- Marca --}}
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">

                        <div class="leading-tight">
                            <div class="text-sm font-semibold tracking-tight">ESCALL PERÚ</div>
                            <div class="text-[11px] text-slate-500">Panel Software</div>
                        </div>
                    </a>

                    @auth
                    {{-- Navegación desktop --}}
                    <nav class="hidden md:flex items-center gap-1 text-sm">
                        @php
                            $linkBase = "px-3 py-2 rounded-xl font-medium transition";
                            $linkOff  = "text-slate-600 hover:bg-slate-100 hover:text-slate-900";
                            $linkOn   = "bg-slate-900 text-white shadow-sm";
                        @endphp

                        <a href="{{ route('dashboard') }}"
                           class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkOn : $linkOff }}">
                            Inicio
                        </a>

                        <a href="{{ route('cargas.index') }}"
                           class="{{ $linkBase }} {{ request()->routeIs('cargas.*') ? $linkOn : $linkOff }}">
                            Cargas
                        </a>

                        <a href="{{ route('tablas.index') }}"
                           class="{{ $linkBase }} {{ request()->routeIs('tablas.*') ? $linkOn : $linkOff }}">
                            Tablas
                        </a>

                        <a href="{{ route('reportes.index') }}"
                           class="{{ $linkBase }} {{ request()->routeIs('reportes.*') ? $linkOn : $linkOff }}">
                            Reportes
                        </a>

                        <a href="{{ route('sms.index') }}"
                           class="{{ $linkBase }} {{ request()->routeIs('sms.*') ? $linkOn : $linkOff }}">
                            SMS
                        </a>
                    </nav>

                    {{-- Acciones --}}
                    <div class="flex items-center gap-2">
                        {{-- Botón menú móvil --}}
                        <button id="btnMobile"
                                class="md:hidden inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                aria-label="Abrir menú">
                            ☰
                        </button>

                        {{-- User chip (opcional, sin romper si no existe name) --}}
                        <div class="hidden md:flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700">
                            <span class="max-w-[140px] truncate">
                                {{ auth()->user()->name ?? 'Usuario' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                                Salir
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>

                {{-- Navegación móvil --}}
                @auth
                <nav id="navMobile" class="md:hidden hidden pb-3">
                    <div class="mt-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                        @php
                            $mBase = "block rounded-xl px-3 py-2 text-sm font-medium transition";
                            $mOff  = "text-slate-700 hover:bg-slate-50";
                            $mOn   = "bg-slate-900 text-white";
                        @endphp

                        <a href="{{ route('dashboard') }}"
                           class="{{ $mBase }} {{ request()->routeIs('dashboard') ? $mOn : $mOff }}">
                            Inicio
                        </a>

                        <a href="{{ route('cargas.index') }}"
                           class="{{ $mBase }} {{ request()->routeIs('cargas.*') ? $mOn : $mOff }}">
                            Cargas
                        </a>

                        <a href="{{ route('tablas.index') }}"
                           class="{{ $mBase }} {{ request()->routeIs('tablas.*') ? $mOn : $mOff }}">
                            Tablas
                        </a>

                        <a href="{{ route('reportes.index') }}"
                           class="{{ $mBase }} {{ request()->routeIs('reportes.*') ? $mOn : $mOff }}">
                            Reportes
                        </a>

                        <a href="{{ route('sms.index') }}"
                           class="{{ $mBase }} {{ request()->routeIs('sms.*') ? $mOn : $mOff }}">
                            SMS
                        </a>

                        <div class="mt-2 border-t border-slate-200 pt-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    Salir
                                </button>
                            </form>
                        </div>
                    </div>
                </nav>
                @endauth
            </div>
        </header>

        {{-- CONTENIDO --}}
        <main class="relative flex-1">
            <div class="mx-auto max-w-7xl px-4 py-5 space-y-4">

                {{-- Breadcrumb --}}
                @hasSection('crumb')
                    <nav class="text-xs text-slate-500" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1">
                            <li>
                                <a href="{{ route('dashboard') }}" class="hover:text-slate-700">
                                    Inicio
                                </a>
                            </li>
                            <li class="text-slate-400">/</li>
                            <li class="truncate">
                                @yield('crumb')
                            </li>
                        </ol>
                    </nav>
                @endif

                {{-- Flash messages --}}
                @if(session('ok') || session('warn') || session('error') || $errors->any())
                    <div class="space-y-2">
                        @if(session('ok'))
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                                {{ session('ok') }}
                            </div>
                        @endif

                        @if(session('warn'))
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                {{ session('warn') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm">
                                <span class="font-semibold">Aviso:</span> {{ session('error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                <div class="font-semibold mb-1">Errores:</div>
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Card principal --}}
                <section class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur">
                    <div class="p-4 md:p-6">
                        @yield('content')
                    </div>
                </section>
            </div>
        </main>

        {{-- FOOTER --}}
        <footer class="relative border-t border-slate-200 bg-white/70 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-3 text-[11px] text-slate-500 flex justify-between items-center">
                <span>© {{ date('Y') }} Escall Perú</span>
                <span class="hidden sm:inline">Panel de Software</span>
            </div>
        </footer>

        {{-- Script menú móvil --}}
        <script>
            (function () {
                const btn = document.getElementById('btnMobile');
                const nav = document.getElementById('navMobile');
                if (!btn || !nav) return;

                btn.addEventListener('click', () => {
                    nav.classList.toggle('hidden');
                });
            })();
        </script>
    </div>
</body>
</html>
