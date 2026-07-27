<?php

namespace App\Console\Commands;

use App\Models\ImportacionExpertis;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class RecoverStaleExpertisImports extends Command
{
    protected $signature = 'expertis:imports:recover-stale
        {--minutes=15 : Minutos sin actividad antes de considerar huérfana una importación}
        {--dry-run : Muestra las importaciones sin modificar su estado}';

    protected $description = 'Detecta importaciones Expertis activas sin heartbeat y las marca como fallidas';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);
        $hasHeartbeat = Schema::hasColumn(
            'importaciones_expertis',
            'heartbeat_at',
        );
        $activityColumn = $hasHeartbeat ? 'heartbeat_at' : 'updated_at';

        $imports = ImportacionExpertis::query()
            ->where('tipo', ImportacionExpertis::TIPO_ASIGNACIONES)
            ->whereIn('estado', ImportacionExpertis::ESTADOS_ACTIVOS)
            ->where(function ($query) use (
                $activityColumn,
                $cutoff,
                $hasHeartbeat,
            ): void {
                $query->where($activityColumn, '<=', $cutoff);

                if ($hasHeartbeat) {
                    $query->orWhere(function ($nested) use ($cutoff): void {
                        $nested->whereNull('heartbeat_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
                }
            })
            ->oldest($activityColumn)
            ->get();

        if ($imports->isEmpty()) {
            $this->info(
                "No hay importaciones de asignaciones sin actividad por {$minutes} minutos.",
            );

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Estado', 'Última actividad', 'Archivo'],
            $imports->map(fn (ImportacionExpertis $import): array => [
                $import->id,
                $import->estado,
                $this->activityAt($import, $hasHeartbeat),
                $import->nombre_original,
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->warn(
                "Simulación: {$imports->count()} importación(es) quedarían marcadas como fallidas.",
            );

            return self::SUCCESS;
        }

        foreach ($imports as $import) {
            $values = [
                'estado' => ImportacionExpertis::ESTADO_FALLIDO,
                'finalizado_at' => now(),
                'mensaje_error' => 'La importación quedó sin actividad y fue detenida para permitir un reintento.',
                'updated_at' => now(),
            ];

            if ($hasHeartbeat) {
                $values['heartbeat_at'] = now();
            }

            ImportacionExpertis::query()
                ->whereKey($import->id)
                ->whereIn('estado', ImportacionExpertis::ESTADOS_ACTIVOS)
                ->update($values);
        }

        $this->info(
            "{$imports->count()} importación(es) huérfanas fueron marcadas como fallidas.",
        );

        return self::SUCCESS;
    }

    private function activityAt(
        ImportacionExpertis $import,
        bool $hasHeartbeat,
    ): string {
        $value = $hasHeartbeat
            ? ($import->heartbeat_at ?? $import->updated_at)
            : $import->updated_at;

        return $value instanceof Carbon
            ? $value->toDateTimeString()
            : (string) $value;
    }
}
