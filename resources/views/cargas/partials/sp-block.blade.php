@php
    // Variables esperadas del controlador
    // $spFi, $spFf, $spPreview, $spCount
@endphp

<section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    
    {{-- ENCABEZADO DE TARJETA --}}
    <div class="border-b border-slate-200 bg-slate-50/50 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800">Carga por SP (Gestiones)</h2>
                <p class="text-xs text-slate-500">Ejecuta el procedimiento almacenado remoto e importa los resultados.</p>
            </div>
        </div>
    </div>

    <div class="p-5">
        {{-- FORMULARIO DE FILTROS --}}
        <form method="GET" action="{{ route('cargas.sp.preview') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            
            <div class="w-full sm:w-auto">
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Fecha Inicio
                </label>
                <input type="date" 
                       name="fi" 
                       value="{{ old('fi', $spFi ?? '') }}" 
                       required
                       class="block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-40">
            </div>

            <div class="w-full sm:w-auto">
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Fecha Fin
                </label>
                <input type="date" 
                       name="ff" 
                       value="{{ old('ff', $spFf ?? '') }}" 
                       required
                       class="block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-40">
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Vista Previa
            </button>
        </form>

        {{-- RESULTADOS --}}
        @if(!is_null($spPreview ?? null))
            
            <div class="mt-6 animate-fade-in-up">
                
                {{-- Info Bar --}}
                <div class="mb-3 flex items-center justify-between rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Se muestran las primeras <strong>100</strong> filas.</span>
                    </div>
                    <div>
                        Total registros detectados: <strong class="text-sm">{{ number_format($spCount ?? 0, 0, ',', '.') }}</strong>
                    </div>
                </div>

                {{-- Tabla Compacta --}}
                <div class="overflow-hidden rounded-lg border border-slate-200 shadow-sm">
                    <div class="overflow-x-auto custom-scrollbar max-h-[400px]">
                        <table class="w-full whitespace-nowrap text-left text-[11px]">
                            <thead class="sticky top-0 z-10 bg-slate-100 text-slate-500">
                                <tr>
                                    @foreach (['Fecha Gestión', 'DNI', 'Teléfono', 'Status', 'Tipificación', 'Observación', 'F. Pago', 'Monto', 'Nombre'] as $th)
                                        <th class="px-3 py-2 font-bold uppercase tracking-wider border-b border-slate-200">{{ $th }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($spPreview as $r)
                                    <tr class="hover:bg-indigo-50/50 transition-colors">
                                        <td class="px-3 py-2 font-medium text-slate-700">{{ $r['fecha_gestion'] }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $r['dni'] }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $r['telefono'] }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                                {{ $r['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $r['tipificacion'] }}</td>
                                        <td class="px-3 py-2 text-slate-500 max-w-xs truncate" title="{{ $r['observacion'] ?? '' }}">
                                            {{ $r['observacion'] ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $r['fecha_pago'] }}</td>
                                        <td class="px-3 py-2 font-mono font-medium text-slate-700 text-right">
                                            {{ $r['monto_pago'] }}
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $r['nombre'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-center text-slate-400 italic">
                                            No se encontraron registros en este rango de fechas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Acciones Finales --}}
                <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                    <form method="POST" action="{{ route('cargas.sp.import') }}">
                        @csrf
                        <input type="hidden" name="fi" value="{{ $spFi }}">
                        <input type="hidden" name="ff" value="{{ $spFf }}">
                        
                        <button type="submit" 
                                onclick="return confirm('ATENCIÓN: Se ELIMINARÁN las gestiones locales entre {{ $spFi }} y {{ $spFf }} y se reemplazarán con la data del SP.\n\n¿Estás seguro de continuar?')"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Importar
                        </button>
                    </form>
                </div>

            </div>
        @endif
    </div>
</section>