@extends('layouts.app')

@section('title', 'Panel Principal')
@section('header_title', 'Panel de Control')
@section('crumb', 'Inicio')

{{-- Refresco automático cada 5 min (Solo en Dashboard) --}}
@push('meta')
    <meta http-equiv="refresh" content="300">
@endpush

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- COLUMNA IZQUIERDA (2/3): KPIs y Acciones --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- 1. TARJETAS DE RESUMEN (KPIs) --}}
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            {{-- KPI: Gestiones --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden group hover:border-blue-300 transition-colors">
                <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-blue-50 transition-transform group-hover:scale-150"></div>
                
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Gestiones Hoy</p>
                    <h3 class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($gestionesHoy ?? 0) }}</h3>
                </div>
                
                <div class="relative z-10 flex items-center gap-1 text-[10px] text-blue-600 font-medium">
                    <span>Ver detalle</span>
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </div>
            </div>

            {{-- KPI: Pagos --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden group hover:border-emerald-300 transition-colors">
                <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-emerald-50 transition-transform group-hover:scale-150"></div>
                
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Recaudo Hoy</p>
                    <h3 class="mt-2 text-3xl font-bold text-slate-800">
                        <span class="text-lg text-slate-400 font-normal">S/</span> {{ number_format($pagosHoy ?? 0, 2) }}
                    </h3>
                </div>
                
                <div class="relative z-10 flex items-center gap-1 text-[10px] text-emerald-600 font-medium">
                    <span>Ver pagos</span>
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </div>
            </div>

            {{-- KPI: Estado / Última Carga --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Última Actualización</p>
                    @if($ultimaCarga)
                        <h3 class="mt-2 text-sm font-semibold text-slate-800">
                            {{ \Carbon\Carbon::parse($ultimaCarga)->format('d M, h:i A') }}
                        </h3>
                        <p class="text-[10px] text-slate-400 mt-1">hace {{ \Carbon\Carbon::parse($ultimaCarga)->diffForHumans(null, true) }}</p>
                    @else
                        <h3 class="mt-2 text-sm text-slate-400 italic">Sin datos recientes</h3>
                    @endif
                </div>
                
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-bold text-green-700">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Sistema Operativo
                    </span>
                </div>
            </div>
        </section>

        {{-- 2. ACCESOS DIRECTOS (Módulos) --}}
        <section>
            <h3 class="mb-3 text-sm font-bold text-slate-800">Acciones Rápidas</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                {{-- Botón Grande: Cargar Pagos --}}
                <a href="{{ route('cargas.index', ['tab' => 'pagos']) }}" class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-400 hover:shadow-md group">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800">Registrar Pago</h4>
                        <p class="text-xs text-slate-500">Ingresar nuevo pago manual.</p>
                    </div>
                </a>

                {{-- Botón Grande: Cargar Gestiones --}}
                <a href="{{ route('cargas.index', ['tab' => 'gestiones', 'modo' => 'sp']) }}" class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-400 hover:shadow-md group">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800">Importar Gestiones</h4>
                        <p class="text-xs text-slate-500">Actualizar desde SP o Excel.</p>
                    </div>
                </a>

                {{-- Botón Pequeño: Tablas --}}
                <a href="{{ route('tablas.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Ver Tablas Mensuales</span>
                    </div>
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>

                {{-- Botón Pequeño: Reportes --}}
                <a href="{{ route('reportes.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Generar Reportes</span>
                    </div>
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>

            </div>
        </section>
    </div>

    {{-- COLUMNA DERECHA (1/3): Tareas --}}
    <div class="lg:col-span-1">
        <aside class="rounded-xl border border-slate-200 bg-white shadow-sm h-full">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-800">Próximas Tareas</h3>
            </div>
            
            <div class="p-4 space-y-4">
                @foreach(($tasks ?? []) as $t)
                    @php
                        $color = match($t['urgency'] ?? 'muted') {
                            'now'  => 'text-red-600 bg-red-50 border-red-100',
                            'soon' => 'text-amber-600 bg-amber-50 border-amber-100',
                            default => 'text-slate-600 bg-slate-50 border-slate-100'
                        };
                        $dot = match($t['urgency'] ?? 'muted') {
                            'now'  => 'bg-red-500',
                            'soon' => 'bg-amber-500',
                            default => 'bg-slate-400'
                        };
                    @endphp

                    <div class="rounded-lg border {{ $color }} p-3 transition hover:shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $dot }}"></span>
                                <span class="text-xs font-bold uppercase tracking-wide opacity-80">En {{ $t['in'] }}</span>
                            </div>
                            @if(($t['urgency'] ?? '') == 'now')
                                <span class="animate-pulse text-[10px] font-bold text-red-600 bg-white px-1.5 py-0.5 rounded border border-red-200">AHORA</span>
                            @endif
                        </div>
                        
                        <h4 class="text-sm font-bold text-slate-800 mb-3">{{ $t['label'] }}</h4>
                        
                        <div class="flex items-center justify-between text-[10px] text-slate-500 border-t border-slate-200/50 pt-2 mt-2">
                            <div>
                                <span class="block opacity-70">Próxima</span>
                                <span class="font-medium">{{ $t['next_at_label'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block opacity-70">Última</span>
                                <span class="font-medium">{{ $t['last_at_label'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(empty($tasks))
                    <div class="text-center py-8 text-slate-400 text-xs">
                        No hay tareas pendientes.
                    </div>
                @endif
            </div>
        </aside>
    </div>

</div>
@endsection