@extends('layouts.app')

@section('title', 'Listas y Registros')
@section('header_title', 'Administrador de Registros')
@section('crumb', 'Operativo / Listas')

@section('content')

{{-- 1. MODAL DE EDICIÓN (Importado) --}}
@include('listas.partials.edit_modal')

{{-- 2. SCRIPT PARA MANEJAR EL MODAL --}}
<script>
  function openModal(item, type) {
    window.dispatchEvent(new CustomEvent('open-edit-modal', {
      detail: { item, type }
    }));
  }
</script>

{{-- 3. NAVEGACIÓN TABS --}}
{{-- Se mejoró el espaciado y la transición de colores para que se sienta más "app nativa" --}}
<div class="flex items-end gap-1 border-b border-slate-200 mb-6 px-1">
    {{-- Tab: Pagos --}}
    <a href="{{ route('listas.index', ['tab'=>'pagos']) }}" 
       class="group relative flex items-center gap-2 px-5 py-2.5 text-xs font-bold transition-all duration-200 rounded-t-lg border-b-[3px] 
       {{ $tab=='pagos' 
          ? 'border-emerald-500 text-emerald-700 bg-white shadow-sm -mb-[1px]' 
          : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">
       <x-heroicon-o-banknotes class="w-4 h-4 {{ $tab=='pagos' ? 'text-emerald-500' : 'text-slate-400 group-hover:text-slate-600' }}"/> 
       Pagos
    </a>

    {{-- Tab: Gestiones --}}
    <a href="{{ route('listas.index', ['tab'=>'gestiones']) }}" 
       class="group relative flex items-center gap-2 px-5 py-2.5 text-xs font-bold transition-all duration-200 rounded-t-lg border-b-[3px] 
       {{ $tab=='gestiones' 
          ? 'border-blue-500 text-blue-700 bg-white shadow-sm -mb-[1px]' 
          : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">
       <x-heroicon-o-phone class="w-4 h-4 {{ $tab=='gestiones' ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600' }}"/> 
       Gestiones
    </a>

    {{-- Tab: Data --}}
    <a href="{{ route('listas.index', ['tab'=>'data']) }}" 
       class="group relative flex items-center gap-2 px-5 py-2.5 text-xs font-bold transition-all duration-200 rounded-t-lg border-b-[3px] 
       {{ $tab=='data' 
          ? 'border-purple-500 text-purple-700 bg-white shadow-sm -mb-[1px]' 
          : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">
       <x-heroicon-o-folder class="w-4 h-4 {{ $tab=='data' ? 'text-purple-500' : 'text-slate-400 group-hover:text-slate-600' }}"/> 
       Cartera
    </a>
</div>

{{-- 4. CONTENEDOR PRINCIPAL --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    
    {{-- BARRA DE FILTROS --}}
    <div class="border-b border-slate-100 bg-slate-50/80 p-5">
        <form method="GET" action="{{ route('listas.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <div class="flex flex-col xl:flex-row gap-4 items-end justify-between">
                
                {{-- GRUPO DE INPUTS (GRID RESPONSIVO) --}}
                <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">

                    {{-- FILTROS: PAGOS --}}
                    @if($tab == 'pagos')
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Documento</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <x-heroicon-o-identification class="h-4 w-4 text-slate-400" />
                                </div>
                                <input type="text" name="dni" value="{{ request('dni') }}" class="block w-full rounded-lg border-slate-300 pl-9 text-xs py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors placeholder:text-slate-300" placeholder="Buscar DNI...">
                            </div>
                        </div>

                        {{-- Rango de Fechas (Agrupado visualmente) --}}
                        <div class="col-span-1 md:col-span-2 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Desde</label>
                                <input type="date" name="fecha_ini" value="{{ request('fecha_ini') }}" class="block w-full rounded-lg border-slate-300 text-xs pl-3 py-2 pr-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-slate-600">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Hasta</label>
                                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="block w-full rounded-lg border-slate-300 text-xs pl-3 py-2 pr-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-slate-600">
                            </div>
                        </div>

                        <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Cosecha</label>

                        <div class="relative">
                            <select
                            name="cosecha"
                            class="block w-full appearance-none rounded-lg border border-slate-300 bg-white
                                    text-xs pl-3 py-2 pr-10 text-slate-600 shadow-sm
                                    focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <option value="">Todas</option>
                            @foreach($listas['cosechas'] ?? [] as $c)
                                <option value="{{ $c }}" {{ request('cosecha')==$c ? 'selected':'' }}>{{ $c }}</option>
                            @endforeach
                            </select>

                            <!-- Flecha SVG -->
                            <svg
                            class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            viewBox="0 0 20 20" fill="currentColor"
                            >
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        </div>
                    {{-- FILTROS: GESTIONES --}}
                    @elseif($tab == 'gestiones')
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Documento</label>
                            <input type="text" name="dni" value="{{ request('dni') }}" class="block w-full rounded-lg border-slate-300 text-xs pl-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="DNI...">
                        </div>

                        <div class="col-span-1 md:col-span-2 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Desde</label>
                                <input type="date" name="fecha_ini" value="{{ request('fecha_ini') }}" class="block w-full rounded-lg border-slate-300 text-xs py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-600">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Hasta</label>
                                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="block w-full rounded-lg border-slate-300 text-xs py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-600">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Resultado</label>
                            <select name="resultado" class="block w-full rounded-lg border-slate-300 text-xs py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-600">
                                <option value="">Todos</option>
                                @foreach($listas['resultados'] ?? [] as $r) 
                                    <option value="{{ $r }}" {{ request('resultado')==$r ? 'selected':'' }}>{{ $r }}</option> 
                                @endforeach
                            </select>
                        </div>

                    {{-- FILTROS: DATA --}}
                    @elseif($tab == 'data')
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Documento</label>
                            <input type="text" name="dni" value="{{ request('dni') }}" class="block w-full rounded-lg border-slate-300 text-xs pl-3 py-2 shadow-sm focus:border-purple-500 focus:ring-purple-500" placeholder="DNI...">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Cartera</label>
                            <select name="cartera" class="block w-full rounded-lg border-slate-300 text-xs py-2 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-slate-600">
                                <option value="">Todas</option>
                                @foreach($listas['carteras'] ?? [] as $c) 
                                    <option value="{{ $c }}" {{ request('cartera')==$c ? 'selected':'' }}>{{ $c }}</option> 
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 ml-1">Cosecha</label>
                            <select name="cosecha" class="block w-full rounded-lg border-slate-300 text-xs py-2 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-slate-600">
                                <option value="">Todas</option>
                                @foreach($listas['cosechas'] ?? [] as $c) 
                                    <option value="{{ $c }}" {{ request('cosecha')==$c ? 'selected':'' }}>{{ $c }}</option> 
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                {{-- BOTONES DE ACCIÓN --}}
                <div class="flex gap-2 w-full xl:w-auto justify-end pt-2 xl:pt-0">
                    <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-700 active:bg-slate-900 transition-all shadow-sm flex items-center gap-2 border border-transparent">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-slate-300"/> 
                        Filtrar
                    </button>
                    
                    <a href="{{ route('listas.export', request()->all()) }}" class="bg-white text-slate-700 px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all shadow-sm border border-slate-200 flex items-center gap-2 group">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-slate-400 group-hover:text-emerald-500 transition-colors"/> 
                        Exportar
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLAS --}}
    <div class="bg-white">
        @if($tab === 'pagos') 
            @include('listas.partials.pagos_table')
        @elseif($tab === 'gestiones') 
            @include('listas.partials.gestiones_table')
        @elseif($tab === 'data') 
            @include('listas.partials.data_table')
        @endif
    </div>

    {{-- PAGINACIÓN --}}
    <div class="p-4 border-t border-slate-100 bg-slate-50/30">
        {{ $data->links() }}
    </div>
</div>
@endsection