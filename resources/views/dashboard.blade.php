@extends('layouts.app')

@section('title','Panel')
@section('crumb','Panel principal')

@section('content')
@php
  // etiqueta del módulo SMS (cuando lo renombres, cambias acá una sola vez)
  $smsLabel = 'SMS';
@endphp

<div class="flex items-start justify-between gap-3">
  <div>
    <h1 class="text-lg font-semibold text-slate-900">Panel principal</h1>
    <p class="text-xs text-slate-500 mt-0.5">Accesos rápidos y estado de automatizaciones.</p>
  </div>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
  {{-- ACCESOS RÁPIDOS --}}
  <section class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white/80 shadow-sm">
    <div class="p-4 md:p-5">
      <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-900">Módulos</div>
        <div class="text-xs text-slate-500">Accesos</div>
      </div>

      <div class="mt-3 grid gap-3 sm:grid-cols-2">
        {{-- Reportes --}}
        <a href="{{ route('reportes.index') }}"
           class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-slate-300">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="h-10 w-10 rounded-xl bg-slate-900/5 flex items-center justify-center">
                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none">
                  <path d="M7 3h10v18H7V3z" stroke="currentColor" stroke-width="2" />
                  <path d="M9 7h6M9 11h6M9 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <div class="font-semibold text-slate-900">Reportes</div>
                <div class="text-[11px] text-slate-500 mt-0.5">Generar y descargar reportes operativos.</div>
              </div>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Ver</span>
          </div>
        </a>

        {{-- Cargas --}}
        <a href="{{ route('cargas.index') }}"
           class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-slate-300">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="h-10 w-10 rounded-xl bg-slate-900/5 flex items-center justify-center">
                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none">
                  <path d="M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M8 7l4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 15v4a2 2 0 002 2h12a2 2 0 002-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <div class="font-semibold text-slate-900">Cargas</div>
                <div class="text-[11px] text-slate-500 mt-0.5">Importar data, gestiones y otros archivos.</div>
              </div>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Gestionar</span>
          </div>
        </a>

        {{-- Tablas --}}
        <a href="{{ route('tablas.index') }}"
           class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-slate-300">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="h-10 w-10 rounded-xl bg-slate-900/5 flex items-center justify-center">
                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none">
                  <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M8 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <div class="font-semibold text-slate-900">Tablas</div>
                <div class="text-[11px] text-slate-500 mt-0.5">Parámetros y tablas del sistema.</div>
              </div>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Configurar</span>
          </div>
        </a>

        {{-- SMS / Solicitudes (por ahora SMS) --}}
        <a href="{{ route('sms.index') }}"
           class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-slate-300">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="h-10 w-10 rounded-xl bg-slate-900/5 flex items-center justify-center">
                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none">
                  <path d="M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <div class="font-semibold text-slate-900">{{ $smsLabel }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">Envío y seguimiento.</div>
              </div>
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Abrir</span>
          </div>
        </a>
      </div>
    </div>
  </section>

  {{-- AUTOMATIZACIONES --}}
  <aside class="rounded-2xl border border-slate-200 bg-white/80 shadow-sm">
    <div class="p-4 md:p-5">
      <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-900">Automatizaciones</div>
        <div class="text-xs text-slate-500">Hora Lima</div>
      </div>

      <div class="mt-3 space-y-3">
        @foreach(($tasks ?? []) as $t)
          @php
            $dot = match($t['urgency'] ?? 'muted') {
              'now'  => 'bg-emerald-500',
              'soon' => 'bg-amber-500',
              default => 'bg-slate-300'
            };
          @endphp

          <div class="rounded-2xl border border-slate-200 bg-white p-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                  <div class="font-semibold text-sm text-slate-900 truncate">{{ $t['label'] }}</div>
                </div>
                <div class="mt-1 text-[11px] text-slate-500 truncate">
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-slate-500">Falta</div>
                <div class="text-sm font-semibold text-slate-900">{{ $t['in'] }}</div>
              </div>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
              <div class="rounded-xl bg-slate-50 border border-slate-200 px-2 py-1">
                <div class="text-slate-500">Próxima</div>
                <div class="font-semibold text-slate-900">{{ $t['next_at_label'] }}</div>
              </div>
              <div class="rounded-xl bg-slate-50 border border-slate-200 px-2 py-1">
                <div class="text-slate-500">Última actividad</div>
                <div class="font-semibold text-slate-900">{{ $t['last_at_label'] }}</div>
              </div>
            </div>
          </div>
        @endforeach

        @if(empty($tasks))
          <div class="rounded-2xl border border-slate-200 bg-white p-3 text-xs text-slate-500">
            No hay tareas configuradas para mostrar.
          </div>
        @endif
      </div>
    </div>
  </aside>
</div>
@endsection
