<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cargas\ImportarDataCsvRequest;
use App\Http\Requests\Cargas\ImportarDataXlsxRequest;
use App\Services\Cargas\DataImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DataController extends Controller
{
    public function form(): Response
    {
        return Inertia::render('Cargas/Data/Index');
    }

    public function importarCsv(
        ImportarDataCsvRequest $request,
        DataImportService $service,
    ): RedirectResponse {
        try {
            $stats = $service->importCsv($request->file('csv'));
        } catch (\Throwable $exception) {
            Log::error('Falló la importación CSV de DATA.', ['exception' => $exception]);

            return back()->with('error', 'No se pudo importar el CSV: '.$exception->getMessage());
        }

        return back()->with('success', $this->summary('CSV', $stats));
    }

    public function upload(
        ImportarDataXlsxRequest $request,
        DataImportService $service,
    ): RedirectResponse {
        try {
            $stats = $service->importXlsx($request->file('archivo'));
        } catch (\Throwable $exception) {
            Log::error('Falló la importación XLSX de DATA.', ['exception' => $exception]);

            return back()->with('error', 'No se pudo importar el XLSX: '.$exception->getMessage());
        }

        return back()->with('success', $this->summary('XLSX', $stats));
    }

    public function templateCsv(): HttpResponse
    {
        $headers = [
            'CODIGO', 'DNI', 'TITULAR', 'CARTERA', 'ENTIDAD', 'COSECHA', 'SUB_CARTERA',
            'PRODUCTO', 'SUB_PRODUCTO', 'HISTORICO', 'DEPARTAMENTO',
            'DEUDA_TOTAL', 'DEUDA_CAPITAL', 'CAMPANIA', 'PORCENTAJE',
        ];
        $example = [
            '0000001587', '00202080', 'LOJAS IPANAQUE PEDRO NICOLAS', 'TEC CENTER',
            'COOP', 'CASTIGO', '-', 'CREDI PYME', '-', 'OCTUBRE . 2025', 'LIMA',
            '1500.00', '932.46', '0.00', '0.35',
        ];
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $headers);
        fputcsv($stream, $example);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return response($contents, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_data.csv"',
        ]);
    }

    private function summary(string $type, array $stats): string
    {
        return "{$type} importado. Procesados: {$stats['processed']} | ".
            "Insertados/actualizados: {$stats['inserted']} | ".
            "Omitidos: {$stats['skipped']} | Fallidos: {$stats['failed']}";
    }
}
