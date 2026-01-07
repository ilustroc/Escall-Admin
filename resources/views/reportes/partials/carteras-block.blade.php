<section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    
    <div class="border-b border-slate-200 bg-slate-50/50 px-5 py-4 flex items-center gap-3">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-800">Reportes de Carteras (Consolidado)</h2>
            <p class="text-xs text-slate-500">Generación masiva de data y métricas mensuales.</p>
        </div>
    </div>

    <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {{-- COLUMNA 1: REPORTE DATA (XLSX) --}}
        <div>
            <h4 class="mb-3 text-xs font-bold text-slate-700 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                Reporte DATA General
            </h4>
            
            <form method="GET" action="{{ route('reportes.carteras.exportDataXlsxFast') }}" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Etiqueta Archivo</label>
                        <input type="text" name="tag" value="{{ $tag ?? '' }}" placeholder="Ej. OCTUBRE25"
                               class="block w-full rounded-lg border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-red-500 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Mes de Trabajo</label>
                        <input type="month" name="mes" value="{{ $mes ?? '' }}" required
                               class="block w-full rounded-lg border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-red-500 focus:ring-red-500">
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-2">
                    <span class="text-[10px] text-slate-400 font-mono">Output: REPORTE_{TAG}_ESCALL.xlsx</span>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-red-700 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Descargar Excel
                    </button>
                </div>
            </form>
        </div>

        {{-- COLUMNA 2: DATA TEC CENTER --}}
        <div class="lg:border-l lg:border-slate-100 lg:pl-8">
            <h4 class="mb-3 text-xs font-bold text-slate-700 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Reporte DATA TEC CENTER
            </h4>

            <form method="GET" action="{{ route('reportes.tec.data') }}" class="space-y-3">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 mb-1">Mes de Trabajo (YYYY-MM)</label>
                    <div class="flex gap-3">
                        <input type="month" name="mes" value="{{ $mes ?? now()->format('Y-m') }}" required
                               class="block w-full rounded-lg border-slate-300 px-3 py-2 text-xs shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        
                        <button type="submit" class="shrink-0 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-4 rounded bg-slate-50 p-3 text-[10px] text-slate-500 leading-relaxed border border-slate-100">
                <strong>Nota Técnica:</strong> El reporte usa el mes seleccionado como base. Las métricas de "Mejor Gestión" fuera del mes se calculan históricamente, mientras que "Intensidad" y "Última Gestión" se limitan al rango del mes elegido.
            </div>
        </div>

    </div>
</section>