<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ReporteKpInvestXlsxFastExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\FiltrarReporteKpInvestRequest;
use App\Queries\Reportes\ReporteKpInvestQuery;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReporteKpInvestController extends Controller
{
    public function index(
        FiltrarReporteKpInvestRequest $request,
        ReporteKpInvestQuery $query,
    ): Response {
        $filters = $request->validated();

        return Inertia::render('Reportes/KpInvest', [
            'filtros' => $filters,
            'total' => $query->count($filters['fi'], $filters['ff']),
        ]);
    }

    public function export(FiltrarReporteKpInvestRequest $request)
    {
        $filters = $request->validated();
        $suffix = $filters['fi'] === $filters['ff']
            ? Carbon::parse($filters['fi'])->format('Ymd')
            : Carbon::parse($filters['fi'])->format('Ymd')
                .'_'.Carbon::parse($filters['ff'])->format('Ymd');

        return (new ReporteKpInvestXlsxFastExport)
            ->forRange($filters['fi'], $filters['ff'])
            ->stream("Reporte KP INVEST {$suffix}.xlsx");
    }
}
