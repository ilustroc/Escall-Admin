@extends('layouts.app')

@section('title', 'Generar Reportes')
@section('header_title', 'Centro de Reportes')
@section('crumb', 'Analítica / Reportes')

@section('content')
    <div class="space-y-6">
        
        {{-- SECCIÓN 1: REPORTES OPERATIVOS (Grid de 2 columnas) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {{-- Reporte Impulse --}}
            @include('reportes.partials.impulse-block')

            {{-- Reporte KP Invest --}}
            @include('reportes.partials.kp-block')

        </div>

        {{-- SECCIÓN 2: REPORTES MASIVOS / CARTERA (Ancho completo) --}}
        <div class="pt-4 border-t border-slate-200">
            <h3 class="mb-4 text-sm font-bold text-slate-500 uppercase tracking-wider">Reportes Consolidados</h3>
            @include('reportes.partials.carteras-block')
        </div>

    </div>
@endsection