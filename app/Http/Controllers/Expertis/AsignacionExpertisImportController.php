<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\ConfirmarAsignacionExpertisRequest;
use App\Http\Requests\Expertis\ImportarAsignacionesExpertisRequest;
use App\Models\ImportacionExpertis;
use App\Services\Expertis\ExpertisImportTemplateService;
use App\Services\Expertis\ExpertisImportWorkflowService;
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
            return response()->json(
                $workflow->queueAssignmentPreparation(
                    $request->file('archivo'),
                    $request->user()->id,
                ),
                202,
            );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function store(
        ConfirmarAsignacionExpertisRequest $request,
        ExpertisImportWorkflowService $workflow,
    ): RedirectResponse {
        $importacion = ImportacionExpertis::query()->findOrFail(
            $request->integer('importacion_id'),
        );

        try {
            $workflow->queueAssignmentImport(
                $importacion,
                $request->user()->id,
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()->with(
                'error',
                'No se pudo encolar la importación de asignaciones.',
            );
        }

        return redirect()
            ->route('expertis.importaciones.show', $importacion)
            ->with(
                'success',
                'La importación de asignaciones quedó en cola.',
            );
    }
}
