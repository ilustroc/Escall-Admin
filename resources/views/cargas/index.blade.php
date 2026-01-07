@extends('layouts.app')
@section('title','Cargas')
@section('crumb','Cargas')

@php
    $tab  = $tab  ?? request('tab','gestiones');
    $modo = $modo ?? request('modo','excel'); // solo para Gestiones
@endphp

@section('content')
<div class="space-y-6">
  {{-- Contenido por tab --}}
  @if($tab==='gestiones')
    @include('cargas.partials.sp-block')

  @elseif($tab==='data')
    @include('cargas.partials.data-block')

  @elseif($tab==='pagos')
    @include('cargas.partials.pagos')
  @endif

</div>
@endsection
