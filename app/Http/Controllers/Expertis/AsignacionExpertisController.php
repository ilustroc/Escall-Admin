<?php

namespace App\Http\Controllers\Expertis;

use App\Exports\ExpertisAsignacionesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\FiltrarAsignacionesExpertisRequest;
use App\Queries\Expertis\AsignacionExpertisQuery;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AsignacionExpertisController extends Controller
{
    public function index(
        FiltrarAsignacionesExpertisRequest $request,
        AsignacionExpertisQuery $asignaciones,
    ): Response {
        $filtros = $asignaciones->filtrosConPeriodoPredeterminado(
            $request->validated(),
        );
        $porPagina = (int) ($filtros['per_page'] ?? 50);

        return Inertia::render('Expertis/Asignaciones/Index', [
            'asignaciones' => $asignaciones->query($filtros)
                ->paginate($porPagina)
                ->withQueryString()
                ->through(fn ($fila) => (array) $fila),
            'filtros' => $filtros,
            'opciones' => $asignaciones->opciones(),
        ]);
    }

    public function export(
        FiltrarAsignacionesExpertisRequest $request,
        AsignacionExpertisQuery $asignaciones,
        ExpertisAsignacionesExport $export,
    ): BinaryFileResponse {
        $filtros = $asignaciones->filtrosConPeriodoPredeterminado(
            $request->validated(),
        );

        return $export->download($filtros);
    }
}
