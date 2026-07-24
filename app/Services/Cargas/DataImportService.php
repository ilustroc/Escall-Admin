<?php

namespace App\Services\Cargas;

use App\Imports\CsvDataImport;
use App\Imports\DataImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DataImportService
{
    public function importXlsx(UploadedFile $file): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $import = new DataImport((int) env('DATA_XLSX_BATCH', 5000));
        DB::transaction(fn () => Excel::import($import, $file), 1);

        return [
            'processed' => $import->processed,
            'inserted' => $import->inserted,
            'skipped' => $import->skipped,
            'failed' => $import->failed,
        ];
    }

    public function importCsv(UploadedFile $file): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        Storage::disk('local')->makeDirectory('imports');
        $relativePath = $file->storeAs(
            'imports',
            'data_'.now()->format('Ymd_His').'.csv',
            'local',
        );
        $absolutePath = Storage::disk('local')->path($relativePath);

        try {
            return DB::transaction(
                fn () => (new CsvDataImport((int) env('DATA_CSV_BATCH', 5000)))
                    ->run($absolutePath),
                1,
            );
        } finally {
            Storage::disk('local')->delete($relativePath);
        }
    }
}
