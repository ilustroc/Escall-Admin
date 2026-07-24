<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ReporteImpulseXlsxFastExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\FiltrarReporteImpulseRequest;
use App\Queries\Reportes\ReporteImpulseQuery;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReporteImpulseController extends Controller
{
    public function index(
        FiltrarReporteImpulseRequest $request,
        ReporteImpulseQuery $query,
    ): Response {
        $filters = $request->validated();

        return Inertia::render('Reportes/Impulse', [
            'filtros' => $filters,
            'total' => $query->count($filters['fi'], $filters['ff'], (int) $filters['equipo']),
        ]);
    }

    public function export(FiltrarReporteImpulseRequest $request)
    {
        $filters = $request->validated();
        $day = Carbon::parse($filters['fi'])->format('Ymd');
        $team = (int) $filters['equipo'];

        return (new ReporteImpulseXlsxFastExport)
            ->forRange($filters['fi'], $filters['ff'], $team)
            ->stream("Gestiones Cartera Propia - {$team} Escall {$day}.xlsx");
    }
}
