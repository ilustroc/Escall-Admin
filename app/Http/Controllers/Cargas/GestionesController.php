<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionesUploadRequest;
use App\Imports\GestionesImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

class GestionesController extends Controller
{
    public function form(): View
    {
        return view('cargas.index');
    }

    public function upload(GestionesUploadRequest $request): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $file = $request->file('archivo');
        $import = new GestionesImport();

        // Import síncrono (para 2k filas va sobrado)
        Excel::import($import, $file);

        return back()->with(
            'ok',
            "Carga XLSX completada. Procesadas: {$import->processed}, insertadas: {$import->inserted}, omitidas: {$import->skipped}, con error: {$import->failed}."
        );
    }

    public function templateCsv()
    {
        $headers = [
            'fecha_gestion','dni','telefono','status','tipificacion',
            'observacion','fecha_pago','monto_pago','nombre'
        ];

        $ejemplo = [[
            '2025-10-24 09:30:00','00202080','977483410','NO CONTACTO','PROMESA DE PAGO',
            'Cliente indica pagar 28/10','2025-10-28','150.00','JUAN RAMIREZ'
        ]];

        $csv = fopen('php://temp','w+');
        fputcsv($csv, $headers);
        foreach ($ejemplo as $r) fputcsv($csv, $r);
        rewind($csv);
        $out = stream_get_contents($csv);
        fclose($csv);

        return Response::make($out, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_gestiones.csv"',
        ]);
    }
}
