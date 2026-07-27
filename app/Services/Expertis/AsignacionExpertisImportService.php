<?php

namespace App\Services\Expertis;

use App\Models\AsignacionExpertis;
use App\Models\ImportacionExpertis;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AsignacionExpertisImportService
{
    private const CAMPOS_ACTUALIZABLES = [
        'importacion_expertis_id',
        'dni',
        'titular',
        'codigo',
        'tipo_cartera',
        'cosecha',
        'sub_cosecha',
        'producto',
        'sub_producto',
        'historico',
        'departamento',
        'deuda_total',
        'deuda_capital',
        'campania',
        'porcentaje',
        'fecha_nacimiento',
        'edad',
        'entidades',
        'negocio',
        'sueldo',
        'situacion_laboral',
        'anio_laboral',
        'sexo',
        'rango_sueldo',
        'anio_castigo',
        'hash_fila',
        'numero_fila_origen',
        'updated_at',
    ];

    public function __construct(
        private readonly AsignacionExpertisPreparationService $preparation,
    ) {}

    public function importarPreparada(ImportacionExpertis $importacion): void
    {
        $inicio = microtime(true);
        $importacion->refresh();

        if (! $this->preparation->hasCompletePreparation($importacion)) {
            throw new RuntimeException(
                'Los bloques preparados no están completos. Repite la preparación del XLSX.',
            );
        }

        $chunks = $this->preparation->chunks($importacion);
        $summary = $importacion->resumen ?? [];
        $validas = (int) ($summary['filas_validas'] ?? 0);
        $erroresPreparacion = (int) ($summary['filas_error'] ?? $importacion->filas_error);
        $resumeChunk = min(
            count($chunks),
            max(0, (int) ($summary['ultimo_chunk_procesado'] ?? 0)),
        );
        $estadisticas = [
            'total' => (int) ($summary['total_archivo'] ?? $importacion->total_filas),
            'procesadas' => $resumeChunk > 0
                ? (int) $importacion->progreso_actual
                : 0,
            'insertadas' => $resumeChunk > 0
                ? (int) $importacion->filas_insertadas
                : 0,
            'actualizadas' => $resumeChunk > 0
                ? (int) $importacion->filas_actualizadas
                : 0,
            'duplicadas' => $resumeChunk > 0
                ? (int) $importacion->filas_duplicadas
                : 0,
            'errores' => $resumeChunk > 0
                ? (int) $importacion->filas_error
                : $erroresPreparacion,
            'lotes' => $resumeChunk,
        ];

        DB::connection()->disableQueryLog();
        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_PROCESANDO,
            'fase' => ImportacionExpertis::FASE_IMPORTANDO,
            'heartbeat_at' => now(),
            'progreso_actual' => $estadisticas['procesadas'],
            'progreso_total' => $validas,
            'filas_insertadas' => $estadisticas['insertadas'],
            'filas_actualizadas' => $estadisticas['actualizadas'],
            'filas_duplicadas' => $estadisticas['duplicadas'],
            'filas_error' => $estadisticas['errores'],
            'finalizado_at' => null,
            'mensaje_error' => null,
        ]);

        foreach ($chunks as $chunkNumber => $chunkPath) {
            if ($chunkNumber < $resumeChunk) {
                continue;
            }

            $rows = iterator_to_array($this->readChunk($chunkPath), false);
            $delta = $this->processChunk($importacion, $rows);

            $estadisticas['procesadas'] += count($rows);
            $estadisticas['insertadas'] += $delta['insertadas'];
            $estadisticas['actualizadas'] += $delta['actualizadas'];
            $estadisticas['duplicadas'] += $delta['duplicadas'];
            $estadisticas['errores'] += $delta['errores'];
            $estadisticas['lotes']++;

            $summary = array_merge($summary, [
                'ultimo_chunk_procesado' => $chunkNumber + 1,
                'total_chunks' => count($chunks),
            ]);

            ImportacionExpertis::query()
                ->whereKey($importacion->id)
                ->update([
                    'heartbeat_at' => now(),
                    'progreso_actual' => $estadisticas['procesadas'],
                    'progreso_total' => $validas,
                    'total_filas' => $estadisticas['total'],
                    'filas_insertadas' => $estadisticas['insertadas'],
                    'filas_actualizadas' => $estadisticas['actualizadas'],
                    'filas_duplicadas' => $estadisticas['duplicadas'],
                    'filas_error' => $estadisticas['errores'],
                    'resumen' => json_encode($summary),
                    'updated_at' => now(),
                ]);
        }

        $segundos = round(microtime(true) - $inicio, 3);
        $memoriaMb = round(memory_get_peak_usage(true) / 1048576, 2);
        $summary = array_merge($summary, [
            'importacion_segundos' => $segundos,
            'memoria_maxima_mb' => max(
                (float) ($summary['memoria_maxima_mb'] ?? 0),
                $memoriaMb,
            ),
            'total_chunks' => count($chunks),
            'lotes' => $estadisticas['lotes'],
            'insertadas' => $estadisticas['insertadas'],
            'actualizadas' => $estadisticas['actualizadas'],
            'duplicadas' => $estadisticas['duplicadas'],
            'errores' => $estadisticas['errores'],
        ]);

        $importacion->update([
            'estado' => $estadisticas['errores'] > 0
                ? ImportacionExpertis::ESTADO_COMPLETADO_CON_ERRORES
                : ImportacionExpertis::ESTADO_COMPLETADO,
            'fase' => ImportacionExpertis::FASE_IMPORTANDO,
            'heartbeat_at' => now(),
            'progreso_actual' => $validas,
            'progreso_total' => $validas,
            'total_filas' => $estadisticas['total'],
            'filas_insertadas' => $estadisticas['insertadas'],
            'filas_actualizadas' => $estadisticas['actualizadas'],
            'filas_duplicadas' => $estadisticas['duplicadas'],
            'filas_error' => $estadisticas['errores'],
            'finalizado_at' => now(),
            'resumen' => $summary,
            'mensaje_error' => null,
        ]);

        Storage::disk('local')->deleteDirectory(
            $this->preparation->preparedDirectory($importacion),
        );

        if (! (bool) config('expertis.guardar_archivo_original', true)) {
            Storage::disk('local')->delete($importacion->ruta_archivo);
            $importacion->update([
                'nombre_guardado' => '',
                'ruta_archivo' => '',
            ]);
        }
    }

    private function processChunk(
        ImportacionExpertis $importacion,
        array $rows,
    ): array {
        return DB::transaction(function () use ($importacion, $rows): array {
            $codes = array_values(array_unique(array_column(
                $rows,
                'codigo_normalizado',
            )));
            $period = (string) ($rows[0]['periodo'] ?? '');
            $company = (string) ($rows[0]['empresa'] ?? '');
            $existing = $codes === []
                ? collect()
                : AsignacionExpertis::query()
                    ->where('periodo', $period)
                    ->where('empresa', $company)
                    ->whereIn('codigo_normalizado', $codes)
                    ->get([
                        'codigo_normalizado',
                        'hash_fila',
                        'importacion_expertis_id',
                        'numero_fila_origen',
                    ])
                    ->keyBy('codigo_normalizado');

            $writes = [];
            $errors = [];
            $seen = [];
            $stats = [
                'insertadas' => 0,
                'actualizadas' => 0,
                'duplicadas' => 0,
                'errores' => 0,
            ];

            foreach ($rows as $row) {
                $code = $row['codigo_normalizado'];
                $hash = $row['hash_fila'];

                if (isset($seen[$code])) {
                    if (hash_equals($seen[$code], $hash)) {
                        $stats['duplicadas']++;
                    } else {
                        $stats['errores']++;
                        $errors[] = $this->duplicateConflictError(
                            $importacion,
                            $row,
                        );
                    }

                    continue;
                }
                $seen[$code] = $hash;

                $current = $existing->get($code);
                if (! $current) {
                    $writes[] = $row;
                    $stats['insertadas']++;

                    continue;
                }

                if ((int) $current->importacion_expertis_id === $importacion->id) {
                    if (hash_equals($current->hash_fila, $hash)) {
                        if (
                            (int) $current->numero_fila_origen
                            === (int) $row['numero_fila_origen']
                        ) {
                            $stats['insertadas']++;
                        } else {
                            $stats['duplicadas']++;
                        }

                        continue;
                    }

                    if (
                        (int) $current->numero_fila_origen
                        < (int) $row['numero_fila_origen']
                    ) {
                        $stats['errores']++;
                        $errors[] = $this->duplicateConflictError(
                            $importacion,
                            $row,
                        );

                        continue;
                    }
                }

                if (hash_equals($current->hash_fila, $hash)) {
                    $stats['duplicadas']++;

                    continue;
                }

                $writes[] = $row;
                $stats['actualizadas']++;
            }

            if ($writes !== []) {
                DB::table('asignaciones_expertis')->upsert(
                    $writes,
                    ['periodo', 'empresa', 'codigo_normalizado'],
                    self::CAMPOS_ACTUALIZABLES,
                );
            }

            if ($errors !== []) {
                DB::table('errores_importacion_expertis')->insert($errors);
            }

            return $stats;
        }, 3);
    }

    private function readChunk(string $path): Generator
    {
        $stream = Storage::disk('local')->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException(
                'No se pudo leer un bloque preparado de asignaciones.',
            );
        }

        try {
            while (($line = fgets($stream)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($row)) {
                    throw new RuntimeException(
                        'Un bloque preparado contiene una fila inválida.',
                    );
                }

                yield $row;
            }
        } finally {
            fclose($stream);
        }
    }

    private function duplicateConflictError(
        ImportacionExpertis $importacion,
        array $row,
    ): array {
        return [
            'importacion_expertis_id' => $importacion->id,
            'numero_fila' => $row['numero_fila_origen'],
            'campo' => 'CODIGO',
            'mensaje' => 'La clave PERIODO + EMPRESA + CODIGO está repetida con información diferente dentro del archivo.',
            'datos_originales' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
