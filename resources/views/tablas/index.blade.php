@extends('layouts.app')

@section('title', 'Analítica de Gestión')
@section('header_title', 'Tablas y Métricas')
@section('crumb', 'Analítica / Tablas')

@section('content')
<div class="space-y-6">

    {{-- Contenido Dinámico --}}
    @if($tab === 'gestiones')
        @include('tablas.partials.gestiones')
    @elseif($tab === 'pagos')
        @include('tablas.partials.pagos')
    @endif

</div>

@section('scripts')
    @vite(['resources/js/tablas.js'])
@endsection

{{-- Estilos para la tabla sticky --}}
<style>
    :root { --bg-head: #f8fafc; --bg-sticky: #ffffff; --bg-total: #f1f5f9; --bg-grand: #e2e8f0; --border-color: #e2e8f0; }
    .complex-table { border-collapse: separate; border-spacing: 0; font-size: 11px; }
    .complex-table th, .complex-table td { padding: 4px 8px; border-bottom: 1px solid var(--border-color); white-space: nowrap; height: 28px; }
    .complex-table thead th { position: sticky; top: 0; background-color: var(--bg-head); z-index: 10; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; }
    .complex-table tfoot th { position: sticky; bottom: 0; background-color: var(--bg-total); z-index: 10; border-top: 2px solid #cbd5e1; color: #334155; font-weight: 700; }
    .complex-table .sticky-left { position: sticky; left: 0; background-color: var(--bg-sticky); z-index: 20; border-right: 1px solid var(--border-color); min-width: 120px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
    .complex-table tr:hover .sticky-left { background-color: #eff6ff; }
    .complex-table thead .sticky-left { z-index: 30; background-color: var(--bg-head); }
    .complex-table tfoot .sticky-left { z-index: 30; background-color: var(--bg-total); }
    .total-col { background-color: var(--bg-total); border-left: 1px solid var(--border-color); }
    .grand-total { background-color: var(--bg-grand) !important; color: #0f172a; }
    .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>
@endsection