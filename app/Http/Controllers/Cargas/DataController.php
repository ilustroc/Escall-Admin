<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\CsvUploadRequest;
use App\Http\Requests\DataUploadRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class DataController extends Controller
{
    public function form(): View
    {
        return view('cargas.index');
    }

    /**
     * Importa DATA desde CSV (streaming, muy grande).
     */
    public function importarCsv(CsvUploadRequest $r): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        // 1) Guardar archivo en storage/app/imports
        Storage::disk('local')->makeDirectory('imports');
        $filename = 'data_'.now()->format('Ymd_His').'.csv';
        $relative = $r->file('csv')->storeAs('imports', $filename, 'local'); // 'imports/xxx.csv'
        $abs      = Storage::path($relative); // <= evita el warning de Intelephense

        if (!is_file($abs)) {
            return back()->withErrors("No se pudo localizar el archivo guardado: $abs");
        }

        // 2) Ejecutar importador en streaming
        try {
            $batchSize = (int) env('DATA_CSV_BATCH', 5000);
            $imp       = new \App\Imports\CsvDataImport(batchSize: $batchSize);
            $stats     = $imp->run($abs);
        } catch (\Throwable $e) {
            Log::error('CSV import failed', ['error' => $e->getMessage()]);
            return back()->withErrors('Error importando CSV: '.$e->getMessage());
        } finally {
            @unlink($abs); // limpia archivo temporal
        }

        return back()->with('ok',
            "CSV importado. Procesados: {$stats['processed']} | Insertados/Actualizados: {$stats['inserted']} | ".
            "Omitidos: {$stats['skipped']} | Fallidos: {$stats['failed']}"
        );
    }

    /**
     * Importa DATA desde XLSX (Maatwebsite Excel + chunk + upsert).
     */
    public function upload(DataUploadRequest $request): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        try {
            $batchSize = (int) env('DATA_XLSX_BATCH', 5000);
            $import    = new \App\Imports\DataImport(batchSize: $batchSize);
            Excel::import($import, $request->file('archivo'));
        } catch (\Throwable $e) {
            Log::error('XLSX import failed', ['error' => $e->getMessage()]);
            return back()->withErrors('Error importando XLSX: '.$e->getMessage());
        }

        return back()->with('ok',
            "XLSX importado. Procesadas: {$import->processed}, insertadas: {$import->inserted}, ".
            "omitidas: {$import->skipped}, con error: {$import->failed}."
        );
    }

    /**
     * Descarga plantilla CSV.
     */
    public function templateCsv()
    {
        $headers = [
            'CODIGO','DNI','TITULAR','CARTERA','ENTIDAD','COSECHA','SUB_CARTERA',
            'PRODUCTO','SUB_PRODUCTO','HISTORICO','DEPARTAMENTO',
            'DEUDA_TOTAL','DEUDA_CAPITAL','CAMPANIA','PORCENTAJE'
        ];

        $ejemplo = [[
            '0000001587','00202080','LOJAS IPANAQUE PEDRO NICOLAS','TEC CENTER','COOP','CASTIGO','-',
            'CREDI PYME','-','OCTUBRE . 2025','LIMA','1500.00','932.46','0.00','0.35'
        ]];

        $tmp = fopen('php://temp','w+');
        fputcsv($tmp, $headers);
        foreach ($ejemplo as $r) fputcsv($tmp, $r);
        rewind($tmp);
        $out = stream_get_contents($tmp);
        fclose($tmp);

        return Response::make($out, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_data.csv"',
        ]);
    }
}
