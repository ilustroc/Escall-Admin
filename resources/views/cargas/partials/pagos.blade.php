@extends('layouts.app')
@section('title','Cargar Pagos')
@section('crumb','Cargas / Pagos')

@section('content')
<div class="space-y-6">
    <header class="space-y-1">
        <h1 class="text-lg font-semibold text-slate-800">Cargar Pagos</h1>
        <p class="text-xs text-slate-500">Registra pagos y guarda un snapshot de la cuenta en el momento.</p>
    </header>

    @if(session('ok'))
      <div class="rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 px-3 py-2 text-xs">
        {{ session('ok') }}
      </div>
    @endif
    @if($errors->any())
      <div class="rounded-lg border border-rose-300 bg-rose-50 text-rose-800 px-3 py-2 text-xs">
          <ul class="list-disc pl-5">
              @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
      </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-4">
        <h2 class="text-sm font-semibold text-slate-800">Formulario</h2>

        {{-- TOP: CODIGO + Rellenar --}}
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-700">Código (N° Cuenta)</label>
                <input id="iCodigo" name="codigo" type="text"
                       class="mt-1 block w-52 rounded-lg border border-slate-300 px-2 py-1.5 text-[11px]">
            </div>
            <button id="btnRellenar" type="button"
                    class="mt-5 inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50">
                Rellenar
            </button>
            <div id="fillMsg" class="text-[11px] text-slate-500"></div>
        </div>

        <form method="POST" action="{{ route('cargas.pagos.store') }}" class="space-y-4">
            @csrf
            {{-- Campos principales --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-[11px] font-medium text-slate-700">Asesor</label>
                    <input name="asesor" type="text" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-700">Fecha</label>
                    <input name="fecha" type="date" required class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-700">Monto S/.</label>
                    <input name="monto" type="number" step="0.01" min="0" required class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-700">Operación</label>
                    <select name="operacion" required class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5">
                        <option value="CANCELACION">CANCELACION</option>
                        <option value="PAGO PARCIAL">PAGO PARCIAL</option>
                        <option value="CUOTA">CUOTA</option>
                    </select>
                </div>
            </div>

            {{-- Snapshot (relleno por lookup) --}}
            <div class="rounded-lg bg-slate-50 border border-dashed border-slate-200 p-3">
                <p class="text-[11px] font-semibold text-slate-700 mb-2">Snapshot de cuenta</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block text-[11px]">DNI</label>
                        <input id="sDni" name="dni" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Nombre</label>
                        <input id="sNombre" name="nombre" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Cartera</label>
                        <input id="sCartera" name="cartera" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Entidad</label>
                        <input id="sEntidad" name="entidad" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Cosecha</label>
                        <input id="sCosecha" name="cosecha" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Departamento</label>
                        <input id="sDpto" name="departamento" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Rango</label>
                        <input id="sRango" name="rango" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Capital</label>
                        <input id="sCapital" name="capital" type="number" step="0.01" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px]">Producto</label>
                        <input id="sProducto" name="producto" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                </div>
            </div>

            {{-- Campo CODIGO real para submit --}}
            <input id="iCodigoHidden" type="hidden" name="codigo">

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
                    Guardar pago
                </button>
            </div>
        </form>
    </section>
</div>

{{-- JS simple --}}
<script>
const el = s => document.querySelector(s);
const btn = el('#btnRellenar');
const code = el('#iCodigo');
const codeHidden = el('#iCodigoHidden');
const msg = el('#fillMsg');

const map = {
  dni: '#sDni', nombre: '#sNombre', cartera: '#sCartera', entidad: '#sEntidad',
  cosecha: '#sCosecha', departamento: '#sDpto', rango: '#sRango',
  capital: '#sCapital', producto: '#sProducto'
};

btn.addEventListener('click', async () => {
  msg.textContent = '';
  const c = (code.value || '').trim();
  if (!c) { msg.textContent = 'Escribe un código primero.'; return; }

  try {
    btn.disabled = true;
    const url = '{{ route('cargas.pagos.lookup') }}' + '?codigo=' + encodeURIComponent(c);
    const res = await fetch(url);
    const j = await res.json();

    if (!j.ok) {
      msg.textContent = j.msg || 'No se pudo rellenar.';
      return;
    }
    // Set snapshot fields
    Object.entries(map).forEach(([k, sel]) => {
      const v = (j.data ?? {})[k] ?? '';
      el(sel).value = v === null ? '' : v;
    });
    codeHidden.value = c;
    msg.textContent = 'Datos cargados desde DATA/ASIGNACIÓN.';
  } catch(e) {
    msg.textContent = 'Error de red al consultar.';
  } finally {
    btn.disabled = false;
  }
});
</script>
@endsection
