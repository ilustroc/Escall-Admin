<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisGestionesExport;
use App\Exports\ExpertisPagosExport;
use App\Http\Controllers\Controller;
use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ExpertisReportController extends Controller
{
    public function index(
        Request $request,
        ExpertisReportQueryService $reportes,
    ): Response {
        $filtros = $this->filtros($request);
        $tipo = $filtros['tipo'] ?? 'gestiones';

        return Inertia::render('Expertis/Reportes/Index', [
            'tipo' => $tipo,
            'filtros' => $filtros,
            'reporte' => $tipo === 'pagos'
                ? $reportes->resumenPagos($filtros)
                : $reportes->resumenGestiones($filtros),
            'opciones' => $reportes->opcionesFiltros(),
        ]);
    }

    public function gestionesExport(Request $request)
    {
        return $this->descargar(new ExpertisGestionesExport($this->filtros($request)), 'gestiones', $request);
    }

    public function pagosExport(Request $request)
    {
        return $this->descargar(new ExpertisPagosExport($this->filtros($request)), 'pagos', $request);
    }

    private function descargar(object $export, string $tipoArchivo, Request $request)
    {
        $formato = $request->validate(['formato' => ['nullable', 'in:xlsx,csv']])['formato'] ?? 'xlsx';
        $writer = $formato === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(
            $export,
            'reporte_'.$tipoArchivo.'_expertis_'.now()->format('Ymd_His').'.'.$formato,
            $writer,
        );
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'tipo' => ['nullable', 'in:gestiones,pagos'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'asesor' => ['nullable', 'string', 'max:150'],
            'equipo' => ['nullable', 'string', 'max:150'],
            'nivel_1' => ['nullable', 'string', 'max:120'],
            'nivel_2' => ['nullable', 'string', 'max:60'],
            'estado' => ['nullable', 'in:PAGO,NO PAGO'],
            'unico' => ['nullable', 'in:0,1'],
            'campania' => ['nullable', 'string', 'max:191'],
            'medio_gestion' => ['nullable', 'string', 'max:100'],
            'cuenta' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20'],
            'ejecutivo' => ['nullable', 'string', 'max:150'],
            'tipo_acuerdo' => ['nullable', 'string', 'max:100'],
            'recaudo' => ['nullable', 'string', 'max:50'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => ['nullable', 'numeric', 'gte:monto_min'],
        ]);
    }
}
