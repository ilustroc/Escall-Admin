<?php

namespace App\Services\Expertis;

use App\Jobs\Expertis\PrepararAsignacionExpertisImport;
use App\Jobs\Expertis\ProcesarAsignacionExpertisImport;
use App\Models\ImportacionExpertis;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ExpertisImportWorkflowService
{
    public function __construct(
        private readonly ExpertisSpreadsheetService $spreadsheet,
        private readonly GestionExpertisImportService $managements,
        private readonly PagoExpertisImportService $payments,
        private readonly AsignacionExpertisPreparationService $assignmentPreparation,
    ) {}

    public function queueAssignmentPreparation(
        UploadedFile $file,
        int $userId,
    ): array {
        $this->ensureQueueAvailable();

        $hash = hash_file('sha256', $file->getRealPath());
        $existing = ImportacionExpertis::query()
            ->where('tipo', ImportacionExpertis::TIPO_ASIGNACIONES)
            ->where('hash_archivo', $hash)
            ->first();

        if ($existing) {
            return $this->assignmentTrackingResult($existing, true);
        }

        $storedPath = $file->storeAs(
            'private/expertis/imports/asignaciones',
            Str::uuid().'.xlsx',
            'local',
        );

        try {
            $importacion = DB::transaction(
                fn (): ImportacionExpertis => ImportacionExpertis::query()->create([
                    'user_id' => $userId,
                    'tipo' => ImportacionExpertis::TIPO_ASIGNACIONES,
                    'nombre_original' => $file->getClientOriginalName(),
                    'nombre_guardado' => basename($storedPath),
                    'ruta_archivo' => $storedPath,
                    'hash_archivo' => $hash,
                    'estado' => ImportacionExpertis::ESTADO_VALIDANDO,
                    'fase' => ImportacionExpertis::FASE_PREPARANDO,
                    'iniciado_at' => now(),
                    'heartbeat_at' => now(),
                    'queued_at' => now(),
                    'progreso_actual' => 0,
                    'progreso_total' => 0,
                    'intentos' => 1,
                    'resumen' => [
                        'preparacion_completada' => false,
                    ],
                ]),
            );
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($storedPath);
            $existing = ImportacionExpertis::query()
                ->where('tipo', ImportacionExpertis::TIPO_ASIGNACIONES)
                ->where('hash_archivo', $hash)
                ->first();

            if (! $existing) {
                throw $exception;
            }

            return $this->assignmentTrackingResult($existing, true);
        }

        PrepararAsignacionExpertisImport::dispatch($importacion->id)
            ->onQueue((string) config('expertis.queue', 'expertis'));

        return $this->assignmentTrackingResult($importacion, false);
    }

    public function queueAssignmentImport(
        ImportacionExpertis $importacion,
        int $userId,
    ): ImportacionExpertis {
        $this->ensureQueueAvailable();
        $importacion->refresh();

        if (
            $importacion->tipo !== ImportacionExpertis::TIPO_ASIGNACIONES
            || ! $importacion->puedeConfirmar()
        ) {
            throw new RuntimeException(
                'La asignación todavía no está lista para importar.',
            );
        }

        if (! $this->assignmentPreparation->hasCompletePreparation($importacion)) {
            throw new RuntimeException(
                'Los bloques preparados están incompletos. Reintenta la validación.',
            );
        }

        $importacion->update([
            'user_id' => $userId,
            'estado' => ImportacionExpertis::ESTADO_EN_COLA,
            'fase' => ImportacionExpertis::FASE_IMPORTANDO,
            'queued_at' => now(),
            'heartbeat_at' => now(),
            'progreso_actual' => 0,
            'progreso_total' => (int) data_get(
                $importacion->resumen,
                'filas_validas',
                0,
            ),
            'finalizado_at' => null,
            'mensaje_error' => null,
        ]);

        ProcesarAsignacionExpertisImport::dispatch($importacion->id)
            ->onQueue((string) config('expertis.queue', 'expertis'));

        return $importacion->refresh();
    }

    public function retryAssignmentImport(
        ImportacionExpertis $importacion,
        int $userId,
    ): ImportacionExpertis {
        $this->ensureQueueAvailable();
        $importacion->refresh();

        if (! $importacion->puedeReintentar()) {
            throw new RuntimeException(
                'Solo se pueden reintentar importaciones de asignaciones fallidas.',
            );
        }

        $lock = Cache::lock(
            'expertis-importacion-'.$importacion->id,
            10,
        );
        if (! $lock->get()) {
            throw new RuntimeException(
                'La importación ya está siendo atendida por otro proceso.',
            );
        }
        $lock->release();

        $nextAttempt = max(1, (int) $importacion->intentos + 1);
        if ($this->assignmentPreparation->hasCompletePreparation($importacion)) {
            $importacion->update([
                'user_id' => $userId,
                'estado' => ImportacionExpertis::ESTADO_EN_COLA,
                'fase' => ImportacionExpertis::FASE_IMPORTANDO,
                'queued_at' => now(),
                'heartbeat_at' => now(),
                'progreso_total' => (int) data_get(
                    $importacion->resumen,
                    'filas_validas',
                    0,
                ),
                'intentos' => $nextAttempt,
                'finalizado_at' => null,
                'mensaje_error' => null,
            ]);

            ProcesarAsignacionExpertisImport::dispatch($importacion->id)
                ->onQueue((string) config('expertis.queue', 'expertis'));

            return $importacion->refresh();
        }

        if (
            ! $importacion->ruta_archivo
            || ! Storage::disk('local')->exists($importacion->ruta_archivo)
        ) {
            throw new RuntimeException(
                'El XLSX original ya no está disponible. Vuelve a cargar el archivo.',
            );
        }

        $absolutePath = Storage::disk('local')->path(
            $importacion->ruta_archivo,
        );
        if (
            ! hash_equals(
                $importacion->hash_archivo,
                hash_file('sha256', $absolutePath),
            )
        ) {
            throw new RuntimeException(
                'El XLSX original no superó la verificación de integridad.',
            );
        }

        $importacion->update([
            'user_id' => $userId,
            'estado' => ImportacionExpertis::ESTADO_VALIDANDO,
            'fase' => ImportacionExpertis::FASE_PREPARANDO,
            'queued_at' => now(),
            'heartbeat_at' => now(),
            'progreso_actual' => 0,
            'progreso_total' => 0,
            'intentos' => $nextAttempt,
            'finalizado_at' => null,
            'mensaje_error' => null,
        ]);

        PrepararAsignacionExpertisImport::dispatch($importacion->id)
            ->onQueue((string) config('expertis.queue', 'expertis'));

        return $importacion->refresh();
    }

    public function preview(
        UploadedFile $file,
        string $type,
        int $userId,
        Session $session,
    ): array {
        $token = Str::random(40);
        $path = $file->storeAs(
            'private/expertis/previews/'.$userId,
            $token.'.xlsx',
            'local',
        );

        try {
            $inspection = $this->spreadsheet->inspeccionar(
                Storage::disk('local')->path($path),
                $type,
            );
        } catch (RuntimeException $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw new RuntimeException(
                'No se pudo leer el contenido del XLSX.',
                0,
                $exception,
            );
        }

        $hash = hash_file('sha256', Storage::disk('local')->path($path));
        $duplicate = ImportacionExpertis::query()
            ->where('tipo', $type)
            ->where('hash_archivo', $hash)
            ->whereIn(
                'estado',
                ImportacionExpertis::ESTADOS_ARCHIVO_PROCESADO,
            )
            ->first();

        if ($inspection['columnas_faltantes'] === []) {
            $session->put('expertis_preview.'.$token, [
                'tipo' => $type,
                'user_id' => $userId,
                'ruta' => $path,
                'nombre_original' => $file->getClientOriginalName(),
                'expira' => now()->addMinutes(
                    config('expertis.preview_ttl_minutes', 120),
                )->timestamp,
            ]);
        } else {
            Storage::disk('local')->delete($path);
        }

        return array_merge($inspection, [
            'token' => $inspection['columnas_faltantes'] === []
                ? $token
                : null,
            'archivo' => [
                'nombre' => $file->getClientOriginalName(),
                'tamano' => $file->getSize(),
                'hash' => $hash,
            ],
            'archivo_duplicado' => $duplicate ? [
                'id' => $duplicate->id,
                'estado' => $duplicate->estado,
                'fecha' => $duplicate->created_at,
            ] : null,
        ]);
    }

    public function import(
        string $token,
        string $type,
        int $userId,
        Session $session,
    ): array {
        $metadata = $session->pull('expertis_preview.'.$token);

        if (
            ! is_array($metadata)
            || ($metadata['tipo'] ?? null) !== $type
            || ($metadata['user_id'] ?? null) !== $userId
            || now()->timestamp > ($metadata['expira'] ?? 0)
            || ! Storage::disk('local')->exists($metadata['ruta'] ?? '')
        ) {
            throw new RuntimeException(
                'La vista previa venció. Selecciona el archivo nuevamente.',
            );
        }

        return match ($type) {
            ExpertisSpreadsheetService::GESTIONES => $this->managements->importar(
                $metadata['ruta'],
                $metadata['nombre_original'],
                $userId,
            ),
            ExpertisSpreadsheetService::PAGOS => $this->payments->importar(
                $metadata['ruta'],
                $metadata['nombre_original'],
                $userId,
            ),
            ExpertisSpreadsheetService::ASIGNACIONES => throw new RuntimeException(
                'Las asignaciones se procesan mediante la cola Expertis.',
            ),
            default => throw new RuntimeException(
                'Tipo de importación Expertis no válido.',
            ),
        };
    }

    private function ensureQueueAvailable(): void
    {
        $connection = (string) config('queue.default', 'sync');

        if (in_array($connection, ['sync', 'null'], true)) {
            throw new RuntimeException(
                'La importación de asignaciones requiere un trabajador de cola. Configura QUEUE_CONNECTION=database e inicia php artisan queue:work.',
            );
        }

        if ($connection === 'database' && ! Schema::hasTable('jobs')) {
            throw new RuntimeException(
                'La cola de base de datos no está instalada. Ejecuta php artisan migrate y luego php artisan queue:work --queue=expertis.',
            );
        }
    }

    private function assignmentTrackingResult(
        ImportacionExpertis $importacion,
        bool $reused,
    ): array {
        return [
            'importacion' => $importacion,
            'importacion_id' => $importacion->id,
            'estado' => $importacion->estado,
            'reutilizada' => $reused,
            'duplicado' => in_array(
                $importacion->estado,
                ImportacionExpertis::ESTADOS_ARCHIVO_PROCESADO,
                true,
            ),
            'seguimiento_url' => route(
                'expertis.importaciones.show',
                $importacion,
            ),
            'estado_url' => route(
                'expertis.importaciones.estado',
                $importacion,
            ),
        ];
    }
}
