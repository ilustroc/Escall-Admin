<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class AsignacionExpertisPreparationService
{
    public function __construct(
        private readonly ExpertisSpreadsheetService $spreadsheet,
        private readonly AsignacionExpertisDataMapper $mapper,
        private readonly NormalizadorExpertisService $normalizador,
    ) {}

    public function preparar(ImportacionExpertis $importacion): void
    {
        $inicio = microtime(true);
        $disk = Storage::disk('local');
        $ruta = $disk->path($importacion->ruta_archivo);

        if (! is_file($ruta)) {
            throw new RuntimeException('El XLSX original ya no está disponible.');
        }

        if (! hash_equals($importacion->hash_archivo, hash_file('sha256', $ruta))) {
            throw new RuntimeException('El XLSX original no superó la verificación de integridad.');
        }

        $directorio = $this->preparedDirectory($importacion);
        $disk->deleteDirectory($directorio);
        $disk->makeDirectory($directorio);
        $importacion->errores()->delete();
        DB::connection()->disableQueryLog();

        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_VALIDANDO,
            'fase' => ImportacionExpertis::FASE_PREPARANDO,
            'heartbeat_at' => now(),
            'progreso_actual' => 0,
            'progreso_total' => 0,
            'total_filas' => 0,
            'filas_insertadas' => 0,
            'filas_actualizadas' => 0,
            'filas_duplicadas' => 0,
            'filas_error' => 0,
            'finalizado_at' => null,
            'mensaje_error' => null,
            'resumen' => [
                'preparacion_completada' => false,
            ],
        ]);

        $tamanoChunk = max(1, (int) config('expertis.chunk_size', 1000));
        $frecuenciaHeartbeat = max(
            1,
            (int) config('expertis.heartbeat_rows', 500),
        );
        $filasNormalizadas = [];
        $errores = [];
        $preview = [];
        $periodos = [];
        $empresas = [];
        $total = 0;
        $validas = 0;
        $totalErrores = 0;
        $totalChunks = 0;

        $inspeccion = $this->spreadsheet->recorrerAsignaciones(
            $ruta,
            function (array $fila) use (
                $importacion,
                $disk,
                $directorio,
                $tamanoChunk,
                $frecuenciaHeartbeat,
                &$filasNormalizadas,
                &$errores,
                &$preview,
                &$periodos,
                &$empresas,
                &$total,
                &$validas,
                &$totalErrores,
                &$totalChunks,
            ): void {
                $total++;
                $datos = $fila['datos'];

                try {
                    $periodo = $this->mapper->periodo($datos['periodo'] ?? null);
                } catch (InvalidArgumentException $exception) {
                    throw new RuntimeException($exception->getMessage(), 0, $exception);
                }

                $periodos[$periodo] = true;
                if (count($periodos) > 1) {
                    throw new RuntimeException(
                        'El archivo debe contener un único periodo de asignación.',
                    );
                }

                $empresa = $this->normalizador->texto(
                    $datos['empresa'] ?? null,
                    50,
                );
                if ($empresa !== 'EXPERTIS') {
                    throw new RuntimeException(
                        'El archivo de asignación contiene una empresa distinta de EXPERTIS.',
                    );
                }
                $empresas[$empresa] = true;

                try {
                    $normalizada = $this->mapper->map(
                        $datos,
                        $importacion->id,
                        $fila['numero_fila'],
                    );
                    $normalizada['created_at'] = now()->format('Y-m-d H:i:s');
                    $normalizada['updated_at'] = now()->format('Y-m-d H:i:s');

                    if (count($preview) < 20) {
                        $preview[] = Arr::except($normalizada, [
                            'importacion_expertis_id',
                            'hash_fila',
                            'numero_fila_origen',
                            'created_at',
                            'updated_at',
                        ]);
                    }

                    $filasNormalizadas[] = $normalizada;
                    $validas++;
                } catch (InvalidArgumentException $exception) {
                    $errores[] = [
                        'importacion_expertis_id' => $importacion->id,
                        'numero_fila' => $fila['numero_fila'],
                        'campo' => null,
                        'mensaje' => $exception->getMessage(),
                        'datos_originales' => json_encode(
                            $fila['originales'],
                            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
                        ),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $totalErrores++;
                }

                if (count($filasNormalizadas) >= $tamanoChunk) {
                    $totalChunks++;
                    $this->writeChunk(
                        $disk,
                        $directorio,
                        $totalChunks,
                        $filasNormalizadas,
                    );
                    $filasNormalizadas = [];
                }

                if (count($errores) >= $tamanoChunk) {
                    DB::table('errores_importacion_expertis')->insert($errores);
                    $errores = [];
                }

                if ($total % $frecuenciaHeartbeat === 0) {
                    $this->heartbeat(
                        $importacion,
                        $total,
                        $validas,
                        $totalErrores,
                    );
                }
            },
        );

        if ($filasNormalizadas !== []) {
            $totalChunks++;
            $this->writeChunk(
                $disk,
                $directorio,
                $totalChunks,
                $filasNormalizadas,
            );
        }

        if ($errores !== []) {
            DB::table('errores_importacion_expertis')->insert($errores);
        }

        $periodo = array_key_first($periodos);
        $empresa = array_key_first($empresas);
        if ($periodo === null || $empresa === null) {
            throw new RuntimeException(
                'El archivo no contiene filas de asignación válidas para preparar.',
            );
        }
        $periodo = (string) $periodo;
        $empresa = (string) $empresa;

        $segundos = round(microtime(true) - $inicio, 3);
        $memoriaMb = round(memory_get_peak_usage(true) / 1048576, 2);

        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_LISTO_PARA_IMPORTAR,
            'fase' => ImportacionExpertis::FASE_ESPERANDO_CONFIRMACION,
            'heartbeat_at' => now(),
            'progreso_actual' => $total,
            'progreso_total' => $total,
            'total_filas' => $total,
            'filas_error' => $totalErrores,
            'resumen' => [
                'periodo' => $periodo,
                'empresa' => $empresa,
                'total_archivo' => $total,
                'filas_validas' => $validas,
                'filas_error' => $totalErrores,
                'total_chunks' => $totalChunks,
                'preview' => $preview,
                'columnas_detectadas' => $inspeccion['columnas_detectadas'],
                'columnas_faltantes' => [],
                'preparacion_completada' => true,
                'preparacion_segundos' => $segundos,
                'memoria_maxima_mb' => $memoriaMb,
            ],
            'mensaje_error' => null,
        ]);
    }

    public function preparedDirectory(ImportacionExpertis|int $importacion): string
    {
        $id = $importacion instanceof ImportacionExpertis
            ? $importacion->id
            : $importacion;

        return trim(
            (string) config(
                'expertis.prepared_directory',
                'private/expertis/prepared',
            ),
            '/',
        ).'/'.$id;
    }

    public function chunks(ImportacionExpertis $importacion): array
    {
        $files = Storage::disk('local')->files(
            $this->preparedDirectory($importacion),
        );
        $files = array_values(array_filter(
            $files,
            static fn (string $file): bool => preg_match(
                '/\/chunk-\d{6}\.jsonl$/',
                str_replace('\\', '/', $file),
            ) === 1,
        ));
        sort($files, SORT_NATURAL);

        return $files;
    }

    public function hasCompletePreparation(ImportacionExpertis $importacion): bool
    {
        $summary = $importacion->resumen ?? [];
        $expected = (int) ($summary['total_chunks'] ?? 0);

        return ($summary['preparacion_completada'] ?? false) === true
            && count($this->chunks($importacion)) === $expected;
    }

    private function writeChunk(
        $disk,
        string $directory,
        int $number,
        array $rows,
    ): void {
        $contents = '';
        foreach ($rows as $row) {
            $contents .= json_encode(
                $row,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            ).PHP_EOL;
        }

        $path = sprintf('%s/chunk-%06d.jsonl', $directory, $number);
        if (! $disk->put($path, $contents)) {
            throw new RuntimeException(
                'No se pudo guardar un bloque preparado de asignaciones.',
            );
        }
    }

    private function heartbeat(
        ImportacionExpertis $importacion,
        int $total,
        int $validas,
        int $errores,
    ): void {
        ImportacionExpertis::query()
            ->whereKey($importacion->id)
            ->update([
                'heartbeat_at' => now(),
                'progreso_actual' => $total,
                'total_filas' => $total,
                'filas_error' => $errores,
                'resumen' => json_encode([
                    'filas_validas' => $validas,
                    'filas_error' => $errores,
                    'preparacion_completada' => false,
                ]),
                'updated_at' => now(),
            ]);
    }
}
