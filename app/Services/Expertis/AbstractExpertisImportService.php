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

    protected function prepararEjecucionLarga(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');

        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
    }

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

        if ($existente && $this->archivoYaProcesado($existente)) {
            $disk->delete($rutaTemporal);

            return ['duplicado' => true, 'importacion' => $existente];
        }

        if (
            $existente
            && $existente->estado !== ImportacionExpertis::ESTADO_FALLIDO
            && $existente->updated_at?->isAfter(now()->subMinutes(15))
        ) {
            throw new RuntimeException(
                'Este archivo tiene una importación en curso. Espera unos minutos antes de reintentar.',
            );
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
            $importacion = $existente ?? new ImportacionExpertis;
            $importacion->fill([
                'user_id' => $userId,
                'tipo' => $tipo,
                'nombre_original' => mb_substr($nombreOriginal, 0, 255),
                'nombre_guardado' => $existente?->nombre_guardado ?? '',
                'ruta_archivo' => $existente?->ruta_archivo ?? '',
                'hash_archivo' => $hash,
                'estado' => ImportacionExpertis::ESTADO_VALIDANDO,
                'total_filas' => 0,
                'filas_insertadas' => 0,
                'filas_actualizadas' => 0,
                'filas_duplicadas' => 0,
                'filas_error' => 0,
                'fecha_minima' => null,
                'fecha_maxima' => null,
                'iniciado_at' => now(),
                'finalizado_at' => null,
                'resumen' => null,
                'mensaje_error' => null,
            ]);
            $importacion->save();
        } catch (QueryException $e) {
            $existente = ImportacionExpertis::query()
                ->where('tipo', $tipo)
                ->where('hash_archivo', $hash)
                ->first();

            if (! $existente) {
                throw $e;
            }

            if ($this->archivoYaProcesado($existente)) {
                $disk->delete($rutaTemporal);

                return ['duplicado' => true, 'importacion' => $existente];
            }

            throw new RuntimeException(
                'Este archivo tiene una importación en curso. Espera unos minutos antes de reintentar.',
                0,
                $e,
            );
        }

        try {
            if ($guardarOriginal) {
                $rutaAnterior = $importacion->ruta_archivo;
                $rutaProceso = $this->promoverArchivo(
                    $rutaTemporal,
                    $rutaFinal,
                    $hash,
                );

                if (
                    $rutaAnterior !== ''
                    && $rutaAnterior !== $rutaFinal
                    && $disk->exists($rutaAnterior)
                ) {
                    $disk->delete($rutaAnterior);
                }
            } else {
                $rutaProceso = $rutaAbsolutaTemporal;
            }
        } catch (\Throwable $e) {
            $this->marcarFallo($importacion, $e);
            throw $e;
        }

        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_PROCESANDO,
            'nombre_guardado' => $nombreGuardado,
            'ruta_archivo' => $rutaFinal,
        ]);

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

    protected function actualizarProgreso(
        ImportacionExpertis $importacion,
        array $estadisticas,
    ): void {
        ImportacionExpertis::query()
            ->whereKey($importacion->id)
            ->update([
                'total_filas' => $estadisticas['total'] ?? 0,
                'filas_insertadas' => $estadisticas['insertadas'] ?? 0,
                'filas_actualizadas' => $estadisticas['actualizadas'] ?? 0,
                'filas_duplicadas' => $estadisticas['duplicadas'] ?? 0,
                'filas_error' => $estadisticas['errores'] ?? 0,
                'fecha_minima' => $estadisticas['fecha_minima'] ?? null,
                'fecha_maxima' => $estadisticas['fecha_maxima'] ?? null,
                'updated_at' => now(),
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

        ImportacionExpertis::query()
            ->whereKey($importacion->id)
            ->update([
                'estado' => ImportacionExpertis::ESTADO_FALLIDO,
                'total_filas' => $estadisticas['total'] ?? $importacion->total_filas,
                'filas_insertadas' => $estadisticas['insertadas'] ?? $importacion->filas_insertadas,
                'filas_actualizadas' => $estadisticas['actualizadas'] ?? $importacion->filas_actualizadas,
                'filas_duplicadas' => $estadisticas['duplicadas'] ?? $importacion->filas_duplicadas,
                'filas_error' => $estadisticas['errores'] ?? $importacion->filas_error,
                'finalizado_at' => now(),
                'mensaje_error' => 'La importación no pudo completarse. Revisa el registro de la aplicación.',
            ]);

        $importacion->refresh();
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

    private function archivoYaProcesado(ImportacionExpertis $importacion): bool
    {
        return in_array(
            $importacion->estado,
            ImportacionExpertis::ESTADOS_ARCHIVO_PROCESADO,
            true,
        );
    }

    private function promoverArchivo(
        string $rutaTemporal,
        string $rutaFinal,
        string $hashEsperado,
    ): string {
        $disk = Storage::disk('local');
        $disk->makeDirectory(dirname($rutaFinal));

        if (! $disk->move($rutaTemporal, $rutaFinal)) {
            if ($disk->exists($rutaFinal)) {
                $disk->delete($rutaFinal);
            }

            if (! $disk->copy($rutaTemporal, $rutaFinal)) {
                throw new RuntimeException(
                    'No se pudo guardar el XLSX en el almacenamiento privado.',
                );
            }

            $disk->delete($rutaTemporal);
        }

        $rutaAbsoluta = $disk->path($rutaFinal);
        if (
            ! is_file($rutaAbsoluta)
            || ! hash_equals($hashEsperado, hash_file('sha256', $rutaAbsoluta))
        ) {
            $disk->delete($rutaFinal);

            throw new RuntimeException(
                'El XLSX guardado no superó la verificación de integridad.',
            );
        }

        return $rutaAbsoluta;
    }
}
