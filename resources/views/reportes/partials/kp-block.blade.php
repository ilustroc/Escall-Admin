<section class="flex flex-col h-full rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden transition-shadow hover:shadow-md">
    
    {{-- ENCABEZADO --}}
    <div class="border-b border-slate-200 bg-amber-50/50 px-5 py-4 flex items-center gap-3">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
            {{-- Icono Pie Chart --}}
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-800">Reporte KP INVEST</h2>
            <p class="text-[10px] text-slate-500">Gestiones consolidadas.</p>
        </div>
    </div>

    {{-- CONTENIDO --}}
    <div class="p-5 flex-1 flex flex-col justify-between">
        
        <form method="GET" action="{{ route('reportes.kp.index') }}" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Inicio</label>
                    <input type="date" name="fi" value="{{ $kpFi ?? '' }}" required 
                           class="block w-full rounded-lg border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Fin</label>
                    <input type="date" name="ff" value="{{ $kpFf ?? '' }}" required 
                           class="block w-full rounded-lg border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <button type="submit" class="w-full inline-flex justify-center items-center rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700 transition">
                Buscar Resultados
            </button>
        </form>

        {{-- BOTÓN EXPORTAR (Solo si hay datos) --}}
        @if(($kpCount ?? 0) > 0)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="{{ route('reportes.kp.export', ['fi'=>$kpFi ?? '', 'ff'=>$kpFf ?? '']) }}"
                   class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold text-slate-700 hover:bg-slate-50 hover:text-amber-600 transition">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exportar Excel Consolidado
                </a>
            </div>
        @endif
    </div>
</section>