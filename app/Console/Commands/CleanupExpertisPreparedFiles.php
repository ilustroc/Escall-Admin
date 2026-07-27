<?php

namespace App\Console\Commands;

use App\Models\ImportacionExpertis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpertisPreparedFiles extends Command
{
    protected $signature = 'expertis:prepared:cleanup
        {--days=7 : Antigüedad mínima de los bloques preparados}
        {--dry-run : Muestra los directorios sin eliminarlos}';

    protected $description = 'Elimina bloques JSONL antiguos de importaciones Expertis terminadas';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days)->timestamp;
        $disk = Storage::disk('local');
        $root = trim(
            (string) config(
                'expertis.prepared_directory',
                'private/expertis/prepared',
            ),
            '/',
        );
        $candidates = [];

        foreach ($disk->directories($root) as $directory) {
            $id = (int) basename(str_replace('\\', '/', $directory));
            if ($id <= 0) {
                continue;
            }

            $files = $disk->files($directory);
            if ($files === []) {
                continue;
            }

            $newest = max(array_map(
                fn (string $file): int => $disk->lastModified($file),
                $files,
            ));
            if ($newest > $cutoff) {
                continue;
            }

            $import = ImportacionExpertis::query()->find($id);
            if (
                $import
                && ! in_array(
                    $import->estado,
                    [
                        ImportacionExpertis::ESTADO_COMPLETADO,
                        ImportacionExpertis::ESTADO_COMPLETADO_CON_ERRORES,
                        ImportacionExpertis::ESTADO_DUPLICADO,
                    ],
                    true,
                )
            ) {
                continue;
            }

            $candidates[] = [
                'id' => $id,
                'directory' => $directory,
                'state' => $import?->estado ?? 'sin registro',
                'files' => count($files),
            ];
        }

        if ($candidates === []) {
            $this->info(
                "No hay bloques preparados eliminables con más de {$days} días.",
            );

            return self::SUCCESS;
        }

        $this->table(
            ['Importación', 'Estado', 'Archivos', 'Directorio'],
            array_map(
                fn (array $item): array => [
                    $item['id'],
                    $item['state'],
                    $item['files'],
                    $item['directory'],
                ],
                $candidates,
            ),
        );

        if ($this->option('dry-run')) {
            $this->warn(
                'Simulación: '.count($candidates).' directorio(s) se eliminarían.',
            );

            return self::SUCCESS;
        }

        foreach ($candidates as $candidate) {
            $disk->deleteDirectory($candidate['directory']);
        }

        $this->info(
            count($candidates).' directorio(s) preparados fueron eliminados.',
        );

        return self::SUCCESS;
    }
}
