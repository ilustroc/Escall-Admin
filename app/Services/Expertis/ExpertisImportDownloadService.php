<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpertisImportDownloadService
{
    public function source(ImportacionExpertis $import): StreamedResponse
    {
        abort_unless(
            $import->ruta_archivo !== ''
            && Storage::disk('local')->exists($import->ruta_archivo),
            404,
        );

        return Storage::disk('local')->download(
            $import->ruta_archivo,
            $import->nombre_original,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function errors(ImportacionExpertis $import): StreamedResponse
    {
        return response()->streamDownload(function () use ($import): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['FILA', 'CAMPO', 'MENSAJE', 'DATOS ORIGINALES']);

            $import->errores()
                ->orderBy('numero_fila')
                ->chunkById(1000, function ($errors) use ($handle): void {
                    foreach ($errors as $error) {
                        fputcsv($handle, [
                            $error->numero_fila,
                            $error->campo,
                            $error->mensaje,
                            json_encode($error->datos_originales, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                });

            fclose($handle);
        }, 'errores_expertis_'.$import->id.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
