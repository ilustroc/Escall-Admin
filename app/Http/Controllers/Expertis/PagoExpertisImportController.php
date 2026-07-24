<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\ImportarPagosExpertisRequest;
use App\Models\ImportacionExpertis;
use App\Services\Expertis\ExpertisSpreadsheetService;
use App\Services\Expertis\PagoExpertisImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        ExpertisSpreadsheetService $spreadsheet,
    ): JsonResponse {
        $archivo = $request->file('archivo');
        $token = Str::random(40);
        $ruta = $archivo->storeAs(
            'private/expertis/previews/'.$request->user()->id,
            $token.'.xlsx',
            'local',
        );

        try {
            $inspeccion = $spreadsheet->inspeccionar(
                Storage::disk('local')->path($ruta),
                ExpertisSpreadsheetService::PAGOS,
            );
        } catch (\Throwable) {
            Storage::disk('local')->delete($ruta);

            return response()->json([
                'message' => 'No se pudo leer el contenido del XLSX.',
            ], 422);
        }

        $hash = hash_file('sha256', Storage::disk('local')->path($ruta));
        $duplicada = ImportacionExpertis::query()
            ->where('tipo', ImportacionExpertis::TIPO_PAGOS)
            ->where('hash_archivo', $hash)
            ->first();

        if ($inspeccion['columnas_faltantes'] === []) {
            $request->session()->put('expertis_preview.'.$token, [
                'tipo' => ExpertisSpreadsheetService::PAGOS,
                'user_id' => $request->user()->id,
                'ruta' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'expira' => now()->addMinutes(
                    config('expertis.preview_ttl_minutes', 120),
                )->timestamp,
            ]);
        } else {
            Storage::disk('local')->delete($ruta);
        }

        return response()->json(array_merge($inspeccion, [
            'token' => $inspeccion['columnas_faltantes'] === [] ? $token : null,
            'archivo' => [
                'nombre' => $archivo->getClientOriginalName(),
                'tamano' => $archivo->getSize(),
                'hash' => $hash,
            ],
            'archivo_duplicado' => $duplicada ? [
                'id' => $duplicada->id,
                'estado' => $duplicada->estado,
                'fecha' => $duplicada->created_at,
            ] : null,
        ]));
    }

    public function store(
        Request $request,
        PagoExpertisImportService $importador,
    ): RedirectResponse {
        $datos = $request->validate([
            'preview_token' => ['required', 'string', 'size:40', 'alpha_num'],
        ]);
        $meta = $request->session()->pull('expertis_preview.'.$datos['preview_token']);

        if (
            ! is_array($meta)
            || ($meta['tipo'] ?? null) !== ExpertisSpreadsheetService::PAGOS
            || ($meta['user_id'] ?? null) !== $request->user()->id
            || now()->timestamp > ($meta['expira'] ?? 0)
            || ! Storage::disk('local')->exists($meta['ruta'] ?? '')
        ) {
            return back()->with('error', 'La vista previa venció. Selecciona el archivo nuevamente.');
        }

        try {
            $resultado = $importador->importar(
                $meta['ruta'],
                $meta['nombre_original'],
                $request->user()->id,
            );
        } catch (\Throwable) {
            return back()->with(
                'error',
                'No se pudo completar la importación. Revisa el formato y el historial de errores.',
            );
        }

        $importacion = $resultado['importacion'];

        return redirect()
            ->route('expertis.importaciones.show', $importacion)
            ->with(
                $resultado['duplicado'] ? 'warn' : 'ok',
                $resultado['duplicado']
                    ? 'Este mismo archivo de pagos ya había sido procesado.'
                    : 'La importación de pagos finalizó.',
            );
    }
}
