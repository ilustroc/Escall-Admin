<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Models\ImportacionExpertis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportacionExpertisController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->render($request);
    }

    public function show(Request $request, ImportacionExpertis $importacion): Response
    {
        return $this->render($request, $importacion);
    }

    public function archivo(ImportacionExpertis $importacion)
    {
        abort_unless(
            $importacion->ruta_archivo !== ''
            && Storage::disk('local')->exists($importacion->ruta_archivo),
            404,
        );

        return Storage::disk('local')->download(
            $importacion->ruta_archivo,
            $importacion->nombre_original,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function errores(ImportacionExpertis $importacion): StreamedResponse
    {
        return response()->streamDownload(function () use ($importacion): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['FILA', 'CAMPO', 'MENSAJE', 'DATOS ORIGINALES']);

            $importacion->errores()
                ->orderBy('numero_fila')
                ->chunkById(1000, function ($errores) use ($handle): void {
                    foreach ($errores as $error) {
                        fputcsv($handle, [
                            $error->numero_fila,
                            $error->campo,
                            $error->mensaje,
                            json_encode($error->datos_originales, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                });

            fclose($handle);
        }, 'errores_expertis_'.$importacion->id.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function render(
        Request $request,
        ?ImportacionExpertis $seleccionada = null,
    ): Response {
        $filtros = $request->validate([
            'tipo' => ['nullable', 'in:gestiones,pagos'],
            'estado' => ['nullable', 'string', 'max:40'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $importaciones = ImportacionExpertis::query()
            ->with('usuario:id,name,email')
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        if ($seleccionada) {
            $seleccionada->load('usuario:id,name,email');
            $seleccionada->setRelation(
                'errores',
                $seleccionada->errores()->orderBy('numero_fila')->limit(100)->get(),
            );
        }

        return Inertia::render('Expertis/Importaciones/Index', [
            'importaciones' => $importaciones,
            'seleccionada' => $seleccionada,
            'filtros' => $filtros,
        ]);
    }
}
