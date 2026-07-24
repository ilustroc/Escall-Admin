<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\AsignacionTecCenterExport;
use App\Exports\ReporteDataCarterasXlsxFastExport;
use App\Exports\ReporteDataTecCenterMesExport;
use App\Exports\ReporteTecCenterMesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\FiltrarReporteCarterasRequest;
use App\Queries\Reportes\ReporteCarterasQuery;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReporteCarterasController extends Controller
{
    public function index(
        FiltrarReporteCarterasRequest $request,
        ReporteCarterasQuery $query,
    ): Response {
        $filters = $request->validated();

        return Inertia::render('Reportes/Carteras', [
            'filtros' => $filters,
            'resumen' => $query->summary($filters['mes']),
        ]);
    }

    public function exportDataXlsxFast(FiltrarReporteCarterasRequest $request)
    {
        $filters = $request->validated();

        return (new ReporteDataCarterasXlsxFastExport)
            ->forMonth($filters['mes'])
            ->stream("REPORTE {$filters['tag']} ESCALL.xlsx");
    }

    public function exportAsignacionTec(FiltrarReporteCarterasRequest $request)
    {
        $filters = $request->validated();

        return Excel::download(
            new AsignacionTecCenterExport,
            "FRMT_RG AGENCIAS EXTERNAS {$filters['lote']}.xlsx",
        );
    }

    public function exportDataTecCenter(FiltrarReporteCarterasRequest $request)
    {
        $month = $request->validated('mes');

        return Excel::download(
            (new ReporteDataTecCenterMesExport)->forMonth($month),
            "REPORTE DATA TEC CENTER {$month}.xlsx",
        );
    }

    public function exportTecCenterData(FiltrarReporteCarterasRequest $request)
    {
        $month = $request->validated('mes');
        $suffix = Carbon::createFromFormat('Y-m', $month)->format('d.m');

        return Excel::download(
            (new ReporteTecCenterMesExport)->forMonth($month),
            "FRMT_GESTIONES CP - 03.01 ({$suffix}).xlsx",
        );
    }
}
