<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Support\Facades\Log;
use Throwable;

class AsignacionExpertisJobFailureService
{
    public function markFailed(int $importacionId, Throwable $exception): void
    {
        Log::error('Fallo en Job de Asignaciones Expertis.', [
            'importacion_id' => $importacionId,
            'tipo' => ImportacionExpertis::TIPO_ASIGNACIONES,
            'exception' => $exception,
        ]);

        ImportacionExpertis::query()
            ->whereKey($importacionId)
            ->update([
                'estado' => ImportacionExpertis::ESTADO_FALLIDO,
                'heartbeat_at' => now(),
                'finalizado_at' => now(),
                'mensaje_error' => 'La importación no pudo completarse en segundo plano. Puedes reintentarla.',
                'updated_at' => now(),
            ]);
    }
}
