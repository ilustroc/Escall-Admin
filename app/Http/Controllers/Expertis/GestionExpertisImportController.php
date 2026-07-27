<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\ConfirmarImportacionExpertisRequest;
use App\Http\Requests\Expertis\ImportarGestionesExpertisRequest;
use App\Models\ImportacionExpertis;
use App\Services\Expertis\ExpertisImportTemplateService;
use App\Services\Expertis\ExpertisImportWorkflowService;
use App\Services\Expertis\ExpertisSpreadsheetService;
use App\Support\UploadLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GestionExpertisImportController extends Controller
{
    public function create(UploadLimit $uploadLimit): Response
    {
        return Inertia::render('Expertis/Importaciones/Gestiones', [
            'ultimaImportacion' => ImportacionExpertis::query()
                ->where('tipo', ImportacionExpertis::TIPO_GESTIONES)
                ->latest()
                ->first(),
            'configuracion' => $uploadLimit->configuration(),
        ]);
    }

    public function plantilla(
        ExpertisImportTemplateService $templates,
    ): StreamedResponse {
        return $templates->gestiones();
    }

    public function preview(
        ImportarGestionesExpertisRequest $request,
        ExpertisImportWorkflowService $workflow,
    ): JsonResponse {
        try {
            return response()->json($workflow->preview(
                $request->file('archivo'),
                ExpertisSpreadsheetService::GESTIONES,
                $request->user()->id,
                $request->session(),
            ));
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function store(
        ConfirmarImportacionExpertisRequest $request,
        ExpertisImportWorkflowService $workflow,
    ): RedirectResponse {
        try {
            $result = $workflow->import(
                $request->validated('preview_token'),
                ExpertisSpreadsheetService::GESTIONES,
                $request->user()->id,
                $request->session(),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()->with('error', 'No se pudo completar la importación. Revisa el historial de errores.');
        }

        return redirect()
            ->route('expertis.importaciones.show', $result['importacion'])
            ->with(
                $result['duplicado'] ? 'warning' : 'success',
                $result['duplicado']
                    ? 'Este mismo archivo de gestiones ya había sido procesado.'
                    : 'La importación de gestiones finalizó.',
            );
    }
}
