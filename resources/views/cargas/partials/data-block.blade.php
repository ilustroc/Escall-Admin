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
        
        {{-- Alerta Informativa (Opcional) --}}
        <div class="mb-5 flex items-start gap-3 rounded-lg bg-blue-50 p-3 text-xs text-blue-800">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p>
                Asegúrate de que el archivo <strong>.xlsx</strong> contenga todas las columnas requeridas (DNI, Nombre, Capital, Deuda, etc.) para evitar errores de importación.
            </p>
        </div>

        {{-- FORMULARIO --}}
        <form method="POST" 
              action="{{ route('cargas.data.upload') }}" 
              enctype="multipart/form-data" 
              class="flex flex-col items-start gap-4 sm:flex-row sm:items-end">
            @csrf
            
            <div class="w-full sm:w-auto">
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Archivo XLSX
                </label>
                
                {{-- Input estilizado --}}
                <input type="file" 
                       name="archivo" 
                       accept=".xlsx" 
                       required
                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-1 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:w-80">
            </div>

            <button type="submit" 
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Subir e Importar
            </button>
        </form>
    </div>
</section>