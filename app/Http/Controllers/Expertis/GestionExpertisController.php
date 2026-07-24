<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisGestionesExport;
use App\Http\Controllers\Controller;
use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class GestionExpertisController extends Controller
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

        $gestiones = $reportes->gestiones($filtros)
            ->paginate($porPagina)
            ->withQueryString()
            ->through(fn ($fila) => (array) $fila);

        return Inertia::render('Expertis/Gestiones/Index', [
            'gestiones' => $gestiones,
            'filtros' => $filtros,
            'opciones' => $reportes->opcionesFiltros(),
        ]);
    }

    public function export(
        Request $request,
        ExpertisReportQueryService $reportes,
    ) {
        $filtros = $this->filtros($request);
        $formato = $request->validate(['formato' => ['nullable', 'in:xlsx,csv']])['formato'] ?? 'xlsx';
        $extension = $formato === 'csv' ? 'csv' : 'xlsx';
        $tipo = $formato === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(
            new ExpertisGestionesExport($filtros),
            'gestiones_expertis_'.now()->format('Ymd_His').'.'.$extension,
            $tipo,
        );
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'asesor' => ['nullable', 'string', 'max:150'],
            'equipo' => ['nullable', 'string', 'max:150'],
            'nivel_1' => ['nullable', 'string', 'max:120'],
            'nivel_2' => ['nullable', 'string', 'max:60'],
            'peso' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'in:PAGO,NO PAGO'],
            'unico' => ['nullable', 'in:0,1'],
            'campania' => ['nullable', 'string', 'max:191'],
            'medio_gestion' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100,250'],
        ]);
    }
}
