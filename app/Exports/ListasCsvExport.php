<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListasCsvExport
{
    public function download(Builder $query, string $tab): StreamedResponse
    {
        $filename = 'reporte_'.$tab.'_'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            $headersWritten = false;

            $query->chunk(1000, function ($rows) use ($handle, &$headersWritten): void {
                foreach ($rows as $row) {
                    $data = $row->toArray();

                    if (! $headersWritten) {
                        fputcsv($handle, array_keys($data));
                        $headersWritten = true;
                    }

                    fputcsv($handle, $data);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
