@extends('layouts.app')

@section('title', 'Gestiones por Asesor')
@section('header_title', 'Tablas de Gestión')
@section('crumb', 'Analítica / Tablas Mensuales')

@section('content')

    <div class="space-y-6">
        
        {{-- SECCIÓN 1: FILTROS Y KPI --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                
                {{-- Formulario --}}
                <form method="GET" action="{{ route('tablas.index') }}" class="flex flex-1 flex-col gap-3 md:flex-row md:items-end">
                    
                    <div class="w-full md:w-auto">
                        <label class="mb-1 block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mes</label>
                        <input type="month" name="mes" value="{{ $mes }}" 
                               class="w-full rounded border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 md:w-40">
                    </div>

                    <button type="submit" 
                            class="inline-flex items-center justify-center rounded bg-blue-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-blue-700 transition">
                        Actualizar
                    </button>

                    <div class="relative w-full md:w-60">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2">
                             <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" id="filtroAgente" placeholder="Buscar agente..." 
                               class="block w-full rounded border-slate-300 pl-8 py-1 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </form>

                {{-- KPIs Compactos --}}
                <div class="flex gap-6 border-t border-slate-100 pt-3 md:border-t-0 md:pt-0">
                    <div class="text-right">
                        <span class="block text-[10px] uppercase text-slate-500 font-semibold">Gestiones</span>
                        <span class="block text-xl font-bold text-slate-800 leading-none">{{ number_format($totalGeneral, 0, ',', '.') }}</span>
                    </div>
                    <div class="w-px bg-slate-200"></div>
                    <div class="text-right">
                        <span class="block text-[10px] uppercase text-slate-500 font-semibold">Agentes</span>
                        <span class="block text-xl font-bold text-slate-800 leading-none">{{ count($table) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: TABLA MENSUAL --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="text-sm font-bold text-slate-800">Detalle Mensual</h3>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                {{-- Nota: Agregué la clase 'compact-view' para reducir tamaños --}}
                <table id="tabla" class="complex-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="sticky-left">AGENTE</th>
                            @foreach($labels as $lbl)
                                <th class="text-right">{{ $lbl }}</th>
                            @endforeach
                            <th class="text-right total-col">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($table as $agente => $byDay)
                            @php $rowTotal = 0; @endphp
                            <tr class="group hover:bg-blue-50 transition-colors">
                                <td class="sticky-left font-semibold text-slate-700 group-hover:bg-blue-100">{{ $agente }}</td>
                                @foreach($days as $d)
                                    @php $v = $byDay[$d] ?? 0; $rowTotal += $v; @endphp
                                    <td class="text-right text-slate-600 {{ $v == 0 ? 'text-slate-200' : '' }}">
                                        {{ $v > 0 ? number_format($v, 0, ',', '.') : '-' }}
                                    </td>
                                @endforeach
                                <td class="text-right total-col font-bold text-slate-900">{{ number_format($rowTotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($days)+2 }}" class="p-6 text-center text-xs text-slate-500">
                                    No hay datos disponibles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="sticky-left">TOTAL DÍA</th>
                            @foreach($days as $d)
                                <th class="text-right">{{ number_format($totalDia[$d] ?? 0, 0, ',', '.') }}</th>
                            @endforeach
                            <th class="text-right grand-total">{{ number_format($totalGeneral, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SECCIÓN 3: VISTA SEMANAL --}}
        <div class="pt-6">
            <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-800">Vista Semanal</h2>
                
                <form method="GET" action="{{ route('tablas.index') }}" class="flex items-center gap-2 bg-white px-2 py-1 rounded border border-slate-200 shadow-sm">
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="week" name="week" value="{{ $week }}" class="border-none bg-transparent p-0 text-xs focus:ring-0">
                    <button type="submit" class="text-blue-600 hover:text-blue-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </button>
                    <span class="text-[10px] text-slate-400 border-l border-slate-200 pl-2 ml-1 uppercase font-semibold">
                        {{ $wLabels[0] ?? '' }} → {{ $wLabels[4] ?? '' }}
                    </span>
                </form>
            </div>

            @php
                $sumRow = function($row, $days){ $t=0; foreach($days as $d){ $t += $row[$d] ?? 0; } return $t; };
                $sumCol = function($tbl, $days){
                    $out = array_fill_keys($days, 0);
                    foreach($tbl as $r){ foreach($days as $d){ $out[$d] += $r[$d]??0; } }
                    return $out;
                };
            @endphp

            <div class="grid grid-cols-1 gap-6">
            @foreach($wCarteras as $cartera)
                @php
                    $tag   = $wCarteraLabel[$cartera] ?? $cartera;
                    $left  = $wPorCosecha[$cartera] ?? [];
                    $right = $wPorRango[$cartera]   ?? [];
                    $totL  = $sumCol($left,  $wDays);
                    $totR  = $sumCol($right, $wDays);
                @endphp

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-slate-50 px-4 py-2 flex items-center gap-2 border-b border-slate-200">
                         <span class="inline-flex items-center justify-center rounded bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800">
                            {{ substr($tag, 0, 3) }}
                         </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-200">
                        
                        {{-- TABLA COSECHAS --}}
                        <div>
                            <div class="px-3 py-1.5 bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                Por Cosechas
                            </div>
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="complex-table w-full">
                                    <thead>
                                        <tr>
                                            <th class="sticky-left">COSECHA</th>
                                            @foreach($wLabels as $lbl) <th class="text-right">{{ $lbl }}</th> @endforeach
                                            <th class="text-right total-col">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($left as $cosecha => $row)
                                            <tr>
                                                <td class="sticky-left font-medium text-slate-700">{{ $cosecha }}</td>
                                                @foreach($wDays as $d) <td class="text-right text-slate-600">{{ $row[$d] ?? 0 }}</td> @endforeach
                                                <td class="text-right total-col font-bold">{{ $sumRow($row, $wDays) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ count($wDays)+2 }}" class="p-2 text-center text-xs text-slate-400 italic">Sin datos</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="sticky-left">TOTAL</th>
                                            @foreach($wDays as $d) <th class="text-right">{{ $totL[$d] }}</th> @endforeach
                                            <th class="text-right grand-total">{{ array_sum($totL) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- TABLA RANGOS --}}
                        <div>
                            <div class="px-3 py-1.5 bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                Por Rango de Deuda
                            </div>
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="complex-table w-full">
                                    <thead>
                                        <tr>
                                            <th class="sticky-left">RANGO</th>
                                            @foreach($wLabels as $lbl) <th class="text-right">{{ $lbl }}</th> @endforeach
                                            <th class="text-right total-col">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($right as $rg => $row)
                                            <tr>
                                                <td class="sticky-left font-medium text-slate-700">{{ $rg }}</td>
                                                @foreach($wDays as $d) <td class="text-right text-slate-600">{{ $row[$d] ?? 0 }}</td> @endforeach
                                                <td class="text-right total-col font-bold">{{ $sumRow($row, $wDays) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ count($wDays)+2 }}" class="p-2 text-center text-xs text-slate-400 italic">Sin datos</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="sticky-left">TOTAL</th>
                                            @foreach($wDays as $d) <th class="text-right">{{ $totR[$d] }}</th> @endforeach
                                            <th class="text-right grand-total">{{ array_sum($totR) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>

    @section('scripts')
        @vite(['resources/js/tablas.js'])
    @endsection

    {{-- ESTILOS: Aquí está la magia para el tamaño --}}
    <style>
        :root {
            --bg-head: #f8fafc;
            --bg-sticky: #ffffff;
            --bg-total: #f1f5f9;
            --bg-grand: #e2e8f0;
            --border-color: #e2e8f0;
        }

        .complex-table { 
            border-collapse: separate; 
            border-spacing: 0; 
            font-size: 11px; /* TAMAÑO DE LETRA REDUCIDO */
        }
        
        /* Celdas más compactas */
        .complex-table th, 
        .complex-table td {
            padding: 4px 8px; /* Padding reducido (era 0.5rem) */
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            height: 28px; /* Altura forzada para compactar */
        }

        /* Sticky Headers */
        .complex-table thead th {
            position: sticky;
            top: 0;
            background-color: var(--bg-head);
            z-index: 10;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-size: 10px; /* Header un poco más pequeño */
        }

        /* Sticky Footer */
        .complex-table tfoot th {
            position: sticky;
            bottom: 0;
            background-color: var(--bg-total);
            z-index: 10;
            border-top: 2px solid #cbd5e1; /* Borde superior más notable */
            color: #334155;
            font-weight: 700;
        }

        /* Sticky Left Column */
        .complex-table .sticky-left {
            position: sticky;
            left: 0;
            background-color: var(--bg-sticky);
            z-index: 20;
            border-right: 1px solid var(--border-color);
            min-width: 120px; /* Ancho mínimo para el nombre */
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .complex-table tr:hover .sticky-left {
            background-color: #eff6ff; /* blue-50 */
        }

        /* Cruces de Sticky */
        .complex-table thead .sticky-left { z-index: 30; background-color: var(--bg-head); }
        .complex-table tfoot .sticky-left { z-index: 30; background-color: var(--bg-total); }

        /* Columna Total (Derecha) */
        .total-col {
            background-color: var(--bg-total);
            border-left: 1px solid var(--border-color);
        }
        .grand-total {
            background-color: var(--bg-grand) !important;
            color: #0f172a;
        }
        
        /* Scrollbar elegante */
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    </style>
@endsection