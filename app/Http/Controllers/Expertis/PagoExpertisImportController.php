<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\ConfirmarImportacionExpertisRequest;
use App\Http\Requests\Expertis\ImportarPagosExpertisRequest;
use App\Models\ImportacionExpertis;
use App\Services\Expertis\ExpertisImportWorkflowService;
use App\Services\Expertis\ExpertisSpreadsheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PagoExpertisImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Expertis/Importaciones/Pagos', [
            'ultimaImportacion' => ImportacionExpertis::query()
                ->where('tipo', ImportacionExpertis::TIPO_PAGOS)
                ->latest()
                ->first(),
            'configuracion' => [
                'max_mb' => config('expertis.import_max_mb', 50),
                'extensiones' => ['xlsx'],
            ],
        ]);
    }

    public function preview(
        ImportarPagosExpertisRequest $request,
        ExpertisImportWorkflowService $workflow,
    ): JsonResponse {
        try {
            return response()->json($workflow->preview(
                $request->file('archivo'),
                ExpertisSpreadsheetService::PAGOS,
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
                ExpertisSpreadsheetService::PAGOS,
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
                    ? 'Este mismo archivo de pagos ya había sido procesado.'
                    : 'La importación de pagos finalizó.',
            );
    }
}
