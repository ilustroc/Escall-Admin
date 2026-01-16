<section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    
    {{-- ENCABEZADO --}}
    <div class="border-b border-slate-200 bg-slate-50/50 px-5 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            {{-- Icono: Database / Folder --}}
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800">Cargar Data de Cartera</h2>
                <p class="text-xs text-slate-500">Actualiza la base maestra de clientes mediante archivo Excel.</p>
            </div>
        </div>

        {{-- Botón Descargar Plantilla --}}
        <a href="{{ route('cargas.data.template.csv') }}" 
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Plantilla CSV
        </a>
    </div>

    {{-- CONTENIDO --}}
    <div class="p-5">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- CARD: XLSX --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-start gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-800">Importar por Excel (XLSX)</h3>
                        <p class="text-xs text-slate-500">Ideal para cargas pequeñas/medianas y revisión manual.</p>
                    </div>
                </div>

                <form method="POST"
                    action="{{ route('cargas.data.upload') }}"
                    enctype="multipart/form-data"
                    class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    @csrf

                    <div class="w-full">
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Archivo XLSX
                        </label>

                        <input type="file"
                            name="archivo"
                            accept=".xlsx"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600
                                    file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-1
                                    file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100
                                    focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                                text-xs font-bold text-white shadow-sm transition hover:bg-blue-700
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Subir e Importar
                    </button>
                </form>
            </div>

            {{-- CARD: CSV (ACOMODADO) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-start gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-8 0h8m-8 0a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-800">Importar por CSV (rápido)</h3>
                        <p class="text-xs text-slate-500">
                            Recomendado para cargas grandes. Más rápido y estable que XLSX.
                        </p>
                    </div>
                </div>

                <form method="POST"
                    action="{{ route('cargas.data.import.csv') }}"
                    enctype="multipart/form-data"
                    class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    @csrf

                    <div class="w-full">
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Archivo CSV
                        </label>

                        <input type="file"
                            name="csv"
                            accept=".csv,text/csv"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600
                                    file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-1
                                    file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100
                                    focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5
                                text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700
                                focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Importar CSV (rápido)
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>