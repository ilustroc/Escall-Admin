<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisGestionesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\FiltrarGestionesExpertisRequest;
use App\Queries\Expertis\ExpertisReportQuery;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class GestionExpertisController extends Controller
{
    public function index(
        FiltrarGestionesExpertisRequest $request,
        ExpertisReportQuery $reports,
    ): Response {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 25);

        return Inertia::render('Expertis/Gestiones/Index', [
            'gestiones' => $reports->gestiones($filters)
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn ($row) => (array) $row),
            'filtros' => $filters,
            'opciones' => $reports->opcionesFiltros(),
        ]);
    }

    public function export(FiltrarGestionesExpertisRequest $request)
    {
        $filters = $request->validated();
        $format = $request->query('formato', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writer = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(
            new ExpertisGestionesExport($filters),
            'gestiones_expertis_'.now()->format('Ymd_His').'.'.$extension,
            $writer,
        );
    }
}
