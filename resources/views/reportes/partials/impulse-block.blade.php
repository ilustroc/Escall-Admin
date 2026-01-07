<section class="flex flex-col h-full rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden transition-shadow hover:shadow-md">
    
    {{-- ENCABEZADO --}}
    <div class="border-b border-slate-200 bg-indigo-50/50 px-5 py-4 flex items-center gap-3">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
            {{-- Icono Chart/Report --}}
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-800">Reporte Impulse</h2>
            <p class="text-[10px] text-slate-500">Gestiones y promesas (Cartera PROPIA 2).</p>
        </div>
    </div>

    {{-- CONTENIDO --}}
    <div class="p-5 flex-1 flex flex-col justify-between">
        
        <form method="GET" action="{{ route('reportes.impulse.index') }}" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Inicio</label>
                    <input type="date" name="fi" value="{{ $impFi ?? '' }}" required 
                           class="block w-full rounded-lg border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Fin</label>
                    <input type="date" name="ff" value="{{ $impFf ?? '' }}" required 
                           class="block w-full rounded-lg border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <button type="submit" class="w-full inline-flex justify-center items-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition">
                Buscar Resultados
            </button>
        </form>

        {{-- BOTONES DE EXPORTACIÓN (Solo si hay datos) --}}
        @if(($impCount ?? 0) > 0)
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                <a href="{{ route('reportes.impulse.export', ['fi' => $impFi ?? '', 'ff' => $impFf ?? '', 'equipo' => 2]) }}"
                   class="inline-flex justify-center items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    XLSX Propia 2
                </a>
                <a href="{{ route('reportes.impulse.export', ['fi' => $impFi ?? '', 'ff' => $impFf ?? '', 'equipo' => 3]) }}"
                   class="inline-flex justify-center items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    XLSX Propia 3
                </a>
            </div>
        @endif
    </div>
</section>