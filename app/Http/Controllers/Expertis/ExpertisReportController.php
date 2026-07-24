<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisGestionesExport;
use App\Exports\ExpertisPagosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\FiltrarReporteExpertisRequest;
use App\Queries\Expertis\ExpertisReportQuery;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ExpertisReportController extends Controller
{
    public function index(
        FiltrarReporteExpertisRequest $request,
        ExpertisReportQuery $reports,
    ): Response {
        $filters = $request->safe()->except('formato');
        $type = $filters['tipo'] ?? 'gestiones';

        return Inertia::render('Expertis/Reportes/Index', [
            'tipo' => $type,
            'filtros' => $filters,
            'reporte' => $type === 'pagos'
                ? $reports->resumenPagos($filters)
                : $reports->resumenGestiones($filters),
            'opciones' => $reports->opcionesFiltros(),
        ]);
    }

    public function gestionesExport(FiltrarReporteExpertisRequest $request)
    {
        return $this->download(
            new ExpertisGestionesExport($request->safe()->except('formato')),
            'gestiones',
            $request->validated('formato', 'xlsx'),
        );
    }

    public function pagosExport(FiltrarReporteExpertisRequest $request)
    {
        return $this->download(
            new ExpertisPagosExport($request->safe()->except('formato')),
            'pagos',
            $request->validated('formato', 'xlsx'),
        );
    }

    private function download(object $export, string $type, string $format)
    {
        $writer = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(
            $export,
            'reporte_'.$type.'_expertis_'.now()->format('Ymd_His').'.'.$format,
            $writer,
        );
    }
}
