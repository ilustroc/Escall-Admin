<?php

namespace App\Jobs\Expertis;

use App\Models\ImportacionExpertis;
use App\Services\Expertis\AsignacionExpertisJobFailureService;
use App\Services\Expertis\AsignacionExpertisPreparationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PrepararAsignacionExpertisImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $importacionId,
    ) {
        $this->timeout = (int) config('expertis.job_timeout', 1800);
        $this->onQueue((string) config('expertis.queue', 'expertis'));
    }

    public function handle(
        AsignacionExpertisPreparationService $preparation,
    ): void {
        $lock = Cache::lock(
            'expertis-importacion-'.$this->importacionId,
            $this->timeout + 300,
        );

        if (! $lock->get()) {
            Log::warning('Se omitió un Job duplicado de preparación Expertis.', [
                'importacion_id' => $this->importacionId,
            ]);

            return;
        }

        try {
            $importacion = ImportacionExpertis::query()
                ->findOrFail($this->importacionId);

            if (
                $importacion->tipo !== ImportacionExpertis::TIPO_ASIGNACIONES
                || $importacion->estado === ImportacionExpertis::ESTADO_LISTO_PARA_IMPORTAR
            ) {
                return;
            }

            $preparation->preparar($importacion);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        app(AsignacionExpertisJobFailureService::class)
            ->markFailed($this->importacionId, $exception);
    }
}
