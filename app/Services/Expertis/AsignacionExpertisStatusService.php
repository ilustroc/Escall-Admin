<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;

class AsignacionExpertisStatusService
{
    public function payload(ImportacionExpertis $importacion): array
    {
        $importacion->refresh();
        $summary = $importacion->resumen ?? [];

        return [
            'id' => $importacion->id,
            'tipo' => $importacion->tipo,
            'archivo' => $importacion->nombre_original,
            'estado' => $importacion->estado,
            'estado_etiqueta' => $this->label($importacion->estado),
            'fase' => $importacion->fase,
            'activo' => in_array(
                $importacion->estado,
                ImportacionExpertis::ESTADOS_ACTIVOS,
                true,
            ),
            'terminal' => in_array(
                $importacion->estado,
                ImportacionExpertis::ESTADOS_TERMINALES,
                true,
            ),
            'puede_confirmar' => $importacion->puedeConfirmar(),
            'puede_reintentar' => $importacion->puedeReintentar(),
            'progreso' => [
                'actual' => (int) $importacion->progreso_actual,
                'total' => (int) $importacion->progreso_total,
                'porcentaje' => $importacion->porcentaje(),
            ],
            'progreso_actual' => (int) $importacion->progreso_actual,
            'progreso_total' => (int) $importacion->progreso_total,
            'porcentaje' => $importacion->porcentaje(),
            'totales' => [
                'filas' => (int) $importacion->total_filas,
                'validas' => (int) ($summary['filas_validas'] ?? 0),
                'insertadas' => (int) $importacion->filas_insertadas,
                'actualizadas' => (int) $importacion->filas_actualizadas,
                'duplicadas' => (int) $importacion->filas_duplicadas,
                'errores' => (int) $importacion->filas_error,
                'chunks' => (int) ($summary['total_chunks'] ?? 0),
            ],
            'total_filas' => (int) $importacion->total_filas,
            'filas_insertadas' => (int) $importacion->filas_insertadas,
            'filas_actualizadas' => (int) $importacion->filas_actualizadas,
            'filas_duplicadas' => (int) $importacion->filas_duplicadas,
            'filas_error' => (int) $importacion->filas_error,
            'periodo' => $summary['periodo'] ?? null,
            'empresa' => $summary['empresa'] ?? null,
            'columnas_detectadas' => $summary['columnas_detectadas'] ?? [],
            'preview' => $summary['preview'] ?? [],
            'intentos' => (int) $importacion->intentos,
            'encolado_at' => $importacion->queued_at?->toIso8601String(),
            'heartbeat_at' => $importacion->heartbeat_at?->toIso8601String(),
            'iniciado_at' => $importacion->iniciado_at?->toIso8601String(),
            'finalizado_at' => $importacion->finalizado_at?->toIso8601String(),
            'mensaje_error' => $importacion->mensaje_error,
            'detalle_url' => route(
                'expertis.importaciones.show',
                $importacion,
            ),
        ];
    }

    public function label(string $state): string
    {
        return match ($state) {
            ImportacionExpertis::ESTADO_PENDIENTE => 'Pendiente',
            ImportacionExpertis::ESTADO_VALIDANDO => 'Validando archivo',
            ImportacionExpertis::ESTADO_LISTO_PARA_IMPORTAR => 'Listo para importar',
            ImportacionExpertis::ESTADO_EN_COLA => 'En cola',
            ImportacionExpertis::ESTADO_PROCESANDO => 'Procesando',
            ImportacionExpertis::ESTADO_COMPLETADO => 'Completado',
            ImportacionExpertis::ESTADO_COMPLETADO_CON_ERRORES => 'Completado con errores',
            ImportacionExpertis::ESTADO_FALLIDO => 'Fallido',
            ImportacionExpertis::ESTADO_DUPLICADO => 'Duplicado',
            default => ucfirst(str_replace('_', ' ', $state)),
        };
    }
}
