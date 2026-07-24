<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisPagosExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\FiltrarPagosExpertisRequest;
use App\Queries\Expertis\ExpertisReportQuery;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class PagoExpertisController extends Controller
{
    public function index(
        FiltrarPagosExpertisRequest $request,
        ExpertisReportQuery $reports,
    ): Response {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 25);

        return Inertia::render('Expertis/Pagos/Index', [
            'pagos' => $reports->pagos($filters)
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn ($row) => (array) $row),
            'resumen' => $reports->resumenPagos($filters)['totales'],
            'filtros' => $filters,
            'opciones' => $reports->opcionesFiltros(),
        ]);
    }

    public function export(FiltrarPagosExpertisRequest $request)
    {
        $filters = $request->validated();
        $format = $request->query('formato', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writer = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(
            new ExpertisPagosExport($filters),
            'pagos_expertis_'.now()->format('Ymd_His').'.'.$extension,
            $writer,
        );
    }
}
