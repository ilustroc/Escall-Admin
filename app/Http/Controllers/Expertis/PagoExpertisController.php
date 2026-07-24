<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisPagosExport;
use App\Http\Controllers\Controller;
use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class PagoExpertisController extends Controller
{
    public function index(
        Request $request,
        ExpertisReportQueryService $reportes,
    ): Response {
        $filtros = $this->filtros($request);
        $porPaginaSolicitado = (int) ($filtros['per_page'] ?? 25);
        $porPagina = in_array($porPaginaSolicitado, [25, 50, 100, 250], true)
            ? $porPaginaSolicitado
            : 25;

        return Inertia::render('Expertis/Pagos/Index', [
            'pagos' => $reportes->pagos($filtros)
                ->paginate($porPagina)
                ->withQueryString()
                ->through(fn ($fila) => (array) $fila),
            'resumen' => $reportes->resumenPagos($filtros)['totales'],
            'filtros' => $filtros,
            'opciones' => $reportes->opcionesFiltros(),
        ]);
    }

    public function export(Request $request)
    {
        $filtros = $this->filtros($request);
        $formato = $request->validate(['formato' => ['nullable', 'in:xlsx,csv']])['formato'] ?? 'xlsx';
        $extension = $formato === 'csv' ? 'csv' : 'xlsx';
        $tipo = $formato === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(
            new ExpertisPagosExport($filtros),
            'pagos_expertis_'.now()->format('Ymd_His').'.'.$extension,
            $tipo,
        );
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'cuenta' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20'],
            'ejecutivo' => ['nullable', 'string', 'max:150'],
            'tipo_acuerdo' => ['nullable', 'string', 'max:100'],
            'recaudo' => ['nullable', 'string', 'max:50'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => ['nullable', 'numeric', 'gte:monto_min'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100,250'],
        ]);
    }
}
