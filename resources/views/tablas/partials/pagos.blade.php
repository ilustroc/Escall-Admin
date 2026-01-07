{{-- resources/views/tablas/partials/pagos.blade.php --}}

{{-- Filtros y KPI --}}
<div class="flex flex-col md:flex-row gap-6 mb-6 items-end justify-between">
    
    <div class="rounded-xl border border-slate-200 bg-white p-4 w-full md:w-auto min-w-[200px]">
        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Recaudo Total</span>
        <div class="text-2xl font-bold text-slate-800 mt-1">S/ {{ number_format($totalMes, 2) }}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 w-full md:w-auto min-w-[120px]">
        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nro. pagos</span>
        <div class="text-2xl font-bold text-slate-800 mt-1"> {{ $nroPagos }}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm w-full md:flex-1">
        <form method="GET" action="{{ route('tablas.index') }}" class="flex items-end gap-4">
            <input type="hidden" name="tab" value="pagos">
            
            <div class="w-full">
                <label class="mb-1 block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mes de Proceso</label>
                <div class="flex gap-2">
                    <input type="month" name="mes" value="{{ $mes }}" 
                        class="w-full rounded border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <button type="submit" class="inline-flex items-center justify-center rounded bg-slate-800 px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-700 transition">
                        Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- TABLA RANKING MATRIZ --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Ranking General - {{ \Carbon\Carbon::parse($mes)->translatedFormat('F Y') }}</h3>
    </div>
    
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-xs border-collapse">
            <thead>
                {{-- Fila 1: Encabezados Principales --}}
                <tr class="text-[10px] uppercase font-bold text-white text-center">
                    {{-- Ejecutivo (Neutro) --}}
                    <th rowspan="2" class="bg-slate-800 w-48 p-2 sticky left-0 z-20 border-r border-slate-600">Ejecutivo</th>
                    
                    {{-- KP INVEST (Azul) --}}
                    <th colspan="2" class="bg-blue-600 p-1 border-r border-blue-500">KP INVEST</th>
                    
                    {{-- TEC CENTER (Verde) --}}
                    <th colspan="2" class="bg-emerald-600 p-1 border-r border-emerald-500">TEC CENTER</th>
                    
                    {{-- IMPULSE (Ámbar) --}}
                    <th colspan="2" class="bg-amber-500 p-1 border-r border-amber-400">IMPULSE</th>
                    
                    {{-- TOTALES (Neutro Gris) --}}
                    <th rowspan="2" class="bg-slate-700 p-2 w-24 border-r border-slate-600">Total S/.</th>
                    <th rowspan="2" class="bg-slate-700 p-2 w-16 border-r border-slate-600">Ops</th>
                    <th rowspan="2" class="bg-slate-800 p-2 w-20">Ticket</th>
                </tr>

                {{-- Fila 2: Sub-columnas (Neutro Claro) --}}
                <tr class="text-[10px] font-bold text-slate-600 bg-slate-100 text-center border-b border-slate-200">
                    {{-- Repetimos para las 3 carteras --}}
                    @for($i=0; $i<3; $i++)
                        <th class="p-1 border-r border-slate-200">Recaudo</th>
                        <th class="p-1 border-r border-slate-200">Cuentas</th>
                    @endfor
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach($matrix as $asesor => $row)
                    <tr class="hover:bg-slate-50 transition-colors group">
                        {{-- Nombre Asesor (Sticky) --}}
                        <td class="sticky left-0 bg-white group-hover:bg-slate-50 p-2 border-r border-slate-200 font-bold text-slate-800 text-[11px]">
                            {{ $asesor }}
                        </td>

                        {{-- Loop por Carteras --}}
                        @foreach($carterasCols as $col)
                            @php 
                                $data = $row['carteras'][$col] ?? ['monto' => 0, 'ops' => 0];
                                $hasData = $data['monto'] > 0;
                            @endphp
                            <td class="text-right p-2 border-r border-slate-100 {{ $hasData ? 'text-slate-700' : 'text-slate-300' }}">
                                {{ $hasData ? number_format($data['monto'], 0) : '-' }}
                            </td>
                            <td class="text-center p-2 border-r border-slate-200 {{ $hasData ? 'text-slate-600' : 'text-slate-200' }}">
                                {{ $hasData ? $data['ops'] : '-' }}
                            </td>
                        @endforeach

                        {{-- Totales Fila --}}
                        <td class="text-right p-2 border-r border-slate-200 font-bold text-slate-600 bg-slate-50/50">
                            {{ number_format($row['total_monto'], 0) }}
                        </td>
                        <td class="text-center p-2 border-r border-slate-200 font-medium text-slate-600 bg-slate-50/50">
                            {{ $row['total_ops'] }}
                        </td>
                        <td class="text-right p-2 border-r border-slate-200 font-bold text-slate-600 bg-slate-50/50">
                            {{ $row['total_ops'] > 0 ? number_format($row['total_monto'] / $row['total_ops'], 0) : 0 }}
                        </td>
                    </tr>
                @endforeach
            </tbody>

            {{-- Footer Totales --}}
            <tfoot class="bg-slate-50 text-slate-800 font-bold text-[11px] border-t-2 border-slate-200">
                <tr>
                    <td class="sticky left-0 bg-slate-100 p-2 text-left border-r border-slate-200 uppercase">
                        TOTAL GENERAL
                    </td>
                    
                    @foreach($carterasCols as $col)
                        <td class="text-right p-2 border-r border-slate-200">
                            {{ number_format($footer['carteras'][$col]['monto'], 0) }}
                        </td>
                        <td class="text-center p-2 border-r border-slate-200 text-slate-600">
                            {{ $footer['carteras'][$col]['ops'] }}
                        </td>
                    @endforeach

                    <td class="text-right p-2 border-r border-slate-200 text-slate-900">
                        {{ number_format($footer['grand_monto'], 0) }}
                    </td>
                    <td class="text-center p-2 border-r border-slate-200 text-slate-900">
                        {{ $footer['grand_ops'] }}
                    </td>
                    <td class="text-right p-2 border-r border-slate-200 text-slate-900">
                        {{ $footer['grand_ops'] > 0 ? number_format($footer['grand_monto'] / $footer['grand_ops'], 0) : 0 }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>