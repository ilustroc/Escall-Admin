<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\ConfirmarImportacionExpertisRequest;
use App\Http\Requests\Expertis\ImportarAsignacionesExpertisRequest;
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

class AsignacionExpertisImportController extends Controller
{
    public function create(UploadLimit $uploadLimit): Response
    {
        return Inertia::render('Expertis/Importaciones/Asignaciones', [
            'ultimaImportacion' => ImportacionExpertis::query()
                ->where('tipo', ImportacionExpertis::TIPO_ASIGNACIONES)
                ->latest()
                ->first(),
            'configuracion' => $uploadLimit->configuration(),
        ]);
    }

    public function plantilla(
        ExpertisImportTemplateService $templates,
    ): StreamedResponse {
        return $templates->asignaciones();
    }

    public function preview(
        ImportarAsignacionesExpertisRequest $request,
        ExpertisImportWorkflowService $workflow,
    ): JsonResponse {
        try {
            return response()->json($workflow->preview(
                $request->file('archivo'),
                ExpertisSpreadsheetService::ASIGNACIONES,
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
                ExpertisSpreadsheetService::ASIGNACIONES,
                $request->user()->id,
                $request->session(),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()->with(
                'error',
                'No se pudo completar la importación de asignaciones. Revisa el historial de errores.',
            );
        }

        return redirect()
            ->route('expertis.importaciones.show', $result['importacion'])
            ->with(
                $result['duplicado'] ? 'warning' : 'success',
                $result['duplicado']
                    ? 'Este mismo archivo de asignaciones ya había sido procesado.'
                    : 'La importación de asignaciones Expertis finalizó.',
            );
    }
}
