<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

abstract class AbstractExpertisImportService
{
    public function __construct(
        protected readonly ExpertisSpreadsheetService $spreadsheet,
        protected readonly NormalizadorExpertisService $normalizador,
    ) {}

    protected function iniciar(
        string $rutaTemporal,
        string $nombreOriginal,
        ?int $userId,
        string $tipo,
    ): array {
        $disk = Storage::disk('local');
        $rutaAbsolutaTemporal = $disk->path($rutaTemporal);

        if (! is_file($rutaAbsolutaTemporal)) {
            throw new RuntimeException('El archivo temporal ya no está disponible.');
        }

        $hash = hash_file('sha256', $rutaAbsolutaTemporal);
        $existente = ImportacionExpertis::query()
            ->where('tipo', $tipo)
            ->where('hash_archivo', $hash)
            ->first();

        if ($existente) {
            $disk->delete($rutaTemporal);

            return ['duplicado' => true, 'importacion' => $existente];
        }

        $guardarOriginal = (bool) config('expertis.guardar_archivo_original', true);
        $nombreGuardado = $guardarOriginal ? Str::uuid().'.xlsx' : '';
        $rutaFinal = $guardarOriginal
            ? sprintf(
                'private/expertis/%s/%s/%s/%s',
                $tipo,
                now()->format('Y'),
                now()->format('m'),
                $nombreGuardado,
            )
            : '';

        try {
            $importacion = ImportacionExpertis::create([
                'user_id' => $userId,
                'tipo' => $tipo,
                'nombre_original' => mb_substr($nombreOriginal, 0, 255),
                'nombre_guardado' => $nombreGuardado,
                'ruta_archivo' => $rutaFinal,
                'hash_archivo' => $hash,
                'estado' => ImportacionExpertis::ESTADO_VALIDANDO,
                'iniciado_at' => now(),
            ]);
        } catch (QueryException $e) {
            $existente = ImportacionExpertis::query()
                ->where('tipo', $tipo)
                ->where('hash_archivo', $hash)
                ->first();

            if (! $existente) {
                throw $e;
            }

            $disk->delete($rutaTemporal);

            return ['duplicado' => true, 'importacion' => $existente];
        }

        try {
            if ($guardarOriginal) {
                $disk->makeDirectory(dirname($rutaFinal));
                if (! $disk->move($rutaTemporal, $rutaFinal)) {
                    throw new RuntimeException('No se pudo mover el XLSX al almacenamiento privado.');
                }
                $rutaProceso = $disk->path($rutaFinal);
            } else {
                $rutaProceso = $rutaAbsolutaTemporal;
            }
        } catch (\Throwable $e) {
            $this->marcarFallo($importacion, $e);
            throw $e;
        }

        $importacion->update(['estado' => ImportacionExpertis::ESTADO_PROCESANDO]);

        return [
            'duplicado' => false,
            'importacion' => $importacion,
            'ruta_proceso' => $rutaProceso,
            'ruta_temporal' => $guardarOriginal ? null : $rutaTemporal,
        ];
    }

    protected function completar(
        ImportacionExpertis $importacion,
        array $estadisticas,
        array $resumen = [],
    ): void {
        $importacion->update([
            'estado' => ($estadisticas['errores'] ?? 0) > 0
                ? ImportacionExpertis::ESTADO_COMPLETADO_CON_ERRORES
                : ImportacionExpertis::ESTADO_COMPLETADO,
            'total_filas' => $estadisticas['total'] ?? 0,
            'filas_insertadas' => $estadisticas['insertadas'] ?? 0,
            'filas_actualizadas' => $estadisticas['actualizadas'] ?? 0,
            'filas_duplicadas' => $estadisticas['duplicadas'] ?? 0,
            'filas_error' => $estadisticas['errores'] ?? 0,
            'fecha_minima' => $estadisticas['fecha_minima'] ?? null,
            'fecha_maxima' => $estadisticas['fecha_maxima'] ?? null,
            'finalizado_at' => now(),
            'resumen' => array_merge($estadisticas, $resumen),
            'mensaje_error' => null,
        ]);
    }

    protected function marcarFallo(
        ImportacionExpertis $importacion,
        \Throwable $excepcion,
        array $estadisticas = [],
    ): void {
        Log::error('Fallo en importación Expertis', [
            'importacion_id' => $importacion->id,
            'tipo' => $importacion->tipo,
            'exception' => $excepcion,
        ]);

        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'total_filas' => $estadisticas['total'] ?? $importacion->total_filas,
            'filas_insertadas' => $estadisticas['insertadas'] ?? $importacion->filas_insertadas,
            'filas_actualizadas' => $estadisticas['actualizadas'] ?? $importacion->filas_actualizadas,
            'filas_duplicadas' => $estadisticas['duplicadas'] ?? $importacion->filas_duplicadas,
            'filas_error' => $estadisticas['errores'] ?? $importacion->filas_error,
            'finalizado_at' => now(),
            'mensaje_error' => 'La importación no pudo completarse. Revisa el registro de la aplicación.',
        ]);
    }

    protected function limpiarTemporal(?string $rutaTemporal): void
    {
        if ($rutaTemporal) {
            Storage::disk('local')->delete($rutaTemporal);
        }
    }

    protected function actualizarRango(array &$estadisticas, string $fecha): void
    {
        if (! isset($estadisticas['fecha_minima']) || $fecha < $estadisticas['fecha_minima']) {
            $estadisticas['fecha_minima'] = $fecha;
        }

        if (! isset($estadisticas['fecha_maxima']) || $fecha > $estadisticas['fecha_maxima']) {
            $estadisticas['fecha_maxima'] = $fecha;
        }
    }
}
