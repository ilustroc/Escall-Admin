<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-3">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-sm font-semibold text-slate-800">Cargar Gestiones (Excel)</h2>
      <p class="text-[11px] text-slate-500">Estructura estándar para insertar en tabla local.</p>
    </div>
    <a href="{{ route('cargas.gestiones.template.csv') }}"
       class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 shadow-sm hover:bg-slate-50">
       Descargar plantilla CSV
    </a>
  </div>

  <form method="POST" action="{{ route('cargas.gestiones.upload') }}" enctype="multipart/form-data"
        class="flex flex-wrap items-end gap-3 text-xs">
    @csrf
    <div>
      <label class="block text-[11px] font-medium text-slate-700">Archivo XLSX</label>
      <input type="file" name="archivo" accept=".xlsx" required
             class="mt-1 block w-64 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[11px] shadow-sm">
    </div>
    <button type="submit" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
      Subir e importar
    </button>
  </form>
</section>
