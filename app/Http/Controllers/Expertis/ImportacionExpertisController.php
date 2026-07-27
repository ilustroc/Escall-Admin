<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\FiltrarImportacionesExpertisRequest;
use App\Models\ImportacionExpertis;
use App\Queries\Expertis\ExpertisImportHistoryQuery;
use App\Services\Expertis\ExpertisImportDownloadService;
use Inertia\Inertia;
use Inertia\Response;

class ImportacionExpertisController extends Controller
{
    public function index(
        FiltrarImportacionesExpertisRequest $request,
        ExpertisImportHistoryQuery $history,
    ): Response {
        return $this->render($request, $history);
    }

    public function show(
        FiltrarImportacionesExpertisRequest $request,
        ImportacionExpertis $importacion,
        ExpertisImportHistoryQuery $history,
    ): Response {
        return $this->render($request, $history, $importacion);
    }

    public function archivo(
        ImportacionExpertis $importacion,
        ExpertisImportDownloadService $download,
    ) {
        return $download->source($importacion);
    }

    public function errores(
        ImportacionExpertis $importacion,
        ExpertisImportDownloadService $download,
    ) {
        return $download->errors($importacion);
    }

    private function render(
        FiltrarImportacionesExpertisRequest $request,
        ExpertisImportHistoryQuery $history,
        ?ImportacionExpertis $selected = null,
    ): Response {
        $filters = $request->validated();

        return Inertia::render('Expertis/Importaciones/Index', [
            'importaciones' => $history->paginate($filters),
            'seleccionada' => $selected ? $history->selected($selected) : null,
            'filtros' => $filters,
            'tipos' => [
                ['value' => ImportacionExpertis::TIPO_ASIGNACIONES, 'label' => 'Asignaciones'],
                ['value' => ImportacionExpertis::TIPO_GESTIONES, 'label' => 'Gestiones'],
                ['value' => ImportacionExpertis::TIPO_PAGOS, 'label' => 'Pagos'],
            ],
        ]);
    }
}
