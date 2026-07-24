<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cargas\ImportarGestionesSpRequest;
use App\Http\Requests\Cargas\PreviewGestionesSpRequest;
use App\Services\Cargas\GestionesSpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GestionesSpController extends Controller
{
    public function form(): Response
    {
        return Inertia::render('Cargas/GestionesSp/Index', [
            'filtros' => [
                'fi' => now()->startOfMonth()->toDateString(),
                'ff' => now()->toDateString(),
            ],
            'preview' => null,
        ]);
    }

    public function preview(
        PreviewGestionesSpRequest $request,
        GestionesSpService $service,
    ): Response|RedirectResponse {
        [$from, $to] = $request->range();

        try {
            return Inertia::render('Cargas/GestionesSp/Index', [
                'filtros' => ['fi' => $from, 'ff' => $to],
                'preview' => $service->preview($from, $to),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Falló la vista previa del SP.', ['exception' => $exception]);

            return redirect()->route('cargas.sp.form')
                ->with('error', 'No se pudo consultar el procedimiento almacenado.');
        }
    }

    public function import(
        ImportarGestionesSpRequest $request,
        GestionesSpService $service,
    ): RedirectResponse {
        [$from, $to] = $request->range();
        try {
            $inserted = $service->import($from, $to);
        } catch (\Throwable $exception) {
            Log::error('Falló la importación desde el SP.', ['exception' => $exception]);

            return back()->with('error', 'La importación falló y el rango local no fue modificado.');
        }

        return redirect()->route('cargas.sp.form')
            ->with('success', "Importación finalizada: {$inserted} gestiones entre {$from} y {$to}.");
    }

    public function templateCsv(): HttpResponse
    {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, [
            'FECHA_GESTION',
            'DNI',
            'TELEFONO',
            'STATUS',
            'TIPIFICACION',
            'OBSERVACION',
            'FECHA_PAGO',
            'MONTO_PAGO',
            'NOMBRE',
        ]);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return response($contents, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_gestiones_sp.csv"',
        ]);
    }
}
