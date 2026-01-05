<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataUploadRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class DataController extends Controller
{
    public function form(): View
    {
        return view('cargas.index');
    }

    public function importarCsv(Request $r): RedirectResponse
    {
        // 1) Validación
        $r->validate([
            'csv' => ['required','file','mimetypes:text/plain,text/csv,application/csv','max:102400'], // 100MB
        ]);

        // 2) Asegura carpeta y guarda con DISK local
        Storage::disk('local')->makeDirectory('imports');
        $filename = 'data_'.now()->format('Ymd_His').'.csv';
        $relative = $r->file('csv')->storeAs('imports', $filename, 'local'); // -> 'imports/archivo.csv'

        // 3) Ruta absoluta fiable
        $abs = Storage::disk('local')->path($relative);

        if (!is_file($abs)) {
            return back()->withErrors("No se pudo encontrar el archivo guardado: $abs. Verifica permisos de storage/.");
        }

        // 4) Ejecuta importador streaming
        @set_time_limit(0);
        @ini_set('memory_limit','1024M');

        $imp   = new \App\Imports\CsvDataImport(batchSize: 2000);
        $stats = $imp->run($abs);

        return back()->with('ok',
            "Procesados: {$stats['processed']} | Insertados/Actualizados: {$stats['inserted']} | Omitidos: {$stats['skipped']} | Fallidos: {$stats['failed']}"
        );
    }

    public function upload(DataUploadRequest $request): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $file   = $request->file('archivo');
        $import = new \App\Imports\DataImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $file);

        return back()->with(
            'ok',
            "Carga XLSX completada. Procesadas: {$import->processed}, insertadas: {$import->inserted}, omitidas: {$import->skipped}, con error: {$import->failed}."
        );
    }

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

        $csv = fopen('php://temp','w+');
        fputcsv($csv, $headers);
        foreach ($ejemplo as $r) fputcsv($csv, $r);
        rewind($csv);
        $out = stream_get_contents($csv);
        fclose($csv);

        return Response::make($out, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_data.csv"',
        ]);
    }
}
