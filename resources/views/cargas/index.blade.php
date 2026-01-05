@extends('layouts.app')
@section('title','Cargas')
@section('crumb','Cargas')

@php
    $tab  = $tab  ?? request('tab','gestiones');
    $modo = $modo ?? request('modo','excel'); // solo para Gestiones
@endphp

@section('content')
<div class="space-y-6">

  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-lg font-semibold text-slate-800">Cargas de información</h1>
      <p class="text-xs text-slate-500">Carga Cartera (Data), Gestiones (Excel o SP) y Pagos.</p>
    </div>

    {{-- Regresar / Salir --}}
    <a href="javascript:history.back()"
       class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
       ← Regresar
    </a>
  </header>

  {{-- Tabs principales --}}
  <nav class="flex gap-2">
    @foreach (['gestiones' => 'Carga Gestiones', 'data' => 'Carga Cartera', 'pagos' => 'Carga Pagos'] as $k => $lbl)
      <a href="{{ route('cargas.index', ['tab'=>$k]) }}"
         class="rounded-xl px-3 py-1.5 text-xs font-medium shadow-sm
                {{ $tab===$k ? 'bg-red-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        {{ $lbl }}
      </a>
    @endforeach
  </nav>

  {{-- Contenido por tab --}}
  @if($tab==='gestiones')
    {{-- Subpestañas: Excel / SP --}}
    <div class="flex gap-2">
      <a href="{{ route('cargas.index',['tab'=>'gestiones','modo'=>'excel']) }}"
         class="rounded-lg px-2.5 py-1 text-[11px] font-medium {{ $modo==='excel' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">
         Por Excel
      </a>
      <a href="{{ route('cargas.index',['tab'=>'gestiones','modo'=>'sp']) }}"
         class="rounded-lg px-2.5 py-1 text-[11px] font-medium {{ $modo==='sp' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">
         Por SP
      </a>
    </div>

    @if($modo==='excel')
      @include('cargas.partials.gestiones-block')
    @else
      @include('cargas.partials.sp-block')
    @endif

  @elseif($tab==='data')
    @include('cargas.partials.data-block')

  @elseif($tab==='pagos')
    @include('cargas.partials.pagos')
  @endif

</div>
@endsection
