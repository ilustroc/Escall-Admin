<?php

namespace App\Services\Expertis;

use App\Models\AsignacionExpertis;
use App\Models\ImportacionExpertis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AsignacionExpertisImportService extends AbstractExpertisImportService
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
        ExpertisSpreadsheetService $spreadsheet,
        NormalizadorExpertisService $normalizador,
        private readonly AsignacionExpertisDataMapper $mapper,
    ) {
        parent::__construct($spreadsheet, $normalizador);
    }

    public function importar(
        string $rutaTemporal,
        string $nombreOriginal,
        ?int $userId,
    ): array {
        $this->prepararEjecucionLarga();

        $inspeccion = $this->spreadsheet->inspeccionar(
            Storage::disk('local')->path($rutaTemporal),
            ExpertisSpreadsheetService::ASIGNACIONES,
        );

        if ($inspeccion['columnas_faltantes'] !== []) {
            throw new RuntimeException(
                'Faltan columnas obligatorias: '.implode(', ', $inspeccion['columnas_faltantes']),
            );
        }

        $contexto = $this->iniciar(
            $rutaTemporal,
            $nombreOriginal,
            $userId,
            ImportacionExpertis::TIPO_ASIGNACIONES,
        );

        if ($contexto['duplicado']) {
            return $contexto;
        }

        /** @var ImportacionExpertis $importacion */
        $importacion = $contexto['importacion'];
        $estadisticas = [
            'total' => 0,
            'insertadas' => 0,
            'actualizadas' => 0,
            'duplicadas' => 0,
            'errores' => 0,
            'lotes' => 0,
        ];
        $lote = [];
        $errores = [];
        $clavesVistas = [];
        $tamanoLote = max(1, (int) config('expertis.chunk_size', 1000));
        $periodo = $inspeccion['periodo_detectado'] ?? null;
        $empresa = $inspeccion['empresa_detectada'] ?? null;

        $clavesRecuperadas = [];
        foreach (DB::table('asignaciones_expertis')
            ->where('importacion_expertis_id', $importacion->id)
            ->select(['periodo', 'empresa', 'codigo_normalizado', 'hash_fila'])
            ->cursor() as $asignacion) {
            $clavesRecuperadas[$this->clave((array) $asignacion)] = $asignacion->hash_fila;
        }

        $importacion->errores()->delete();
        DB::connection()->disableQueryLog();

        try {
            foreach ($this->spreadsheet->filas(
                $contexto['ruta_proceso'],
                ExpertisSpreadsheetService::ASIGNACIONES,
            ) as $fila) {
                $estadisticas['total']++;

                try {
                    $asignacion = $this->mapper->map(
                        $fila['datos'],
                        $importacion->id,
                        $fila['numero_fila'],
                    );
                    $clave = $this->clave($asignacion);

                    if (isset($clavesVistas[$clave])) {
                        if (hash_equals($clavesVistas[$clave], $asignacion['hash_fila'])) {
                            $estadisticas['duplicadas']++;
                        } else {
                            $estadisticas['errores']++;
                            $errores[] = $this->error(
                                $importacion->id,
                                $fila,
                                'CODIGO',
                                'La clave PERIODO + EMPRESA + CODIGO está repetida con información diferente dentro del archivo.',
                            );
                        }
                    } else {
                        $clavesVistas[$clave] = $asignacion['hash_fila'];
                        $lote[] = $asignacion;
                    }
                } catch (\InvalidArgumentException $exception) {
                    $estadisticas['errores']++;
                    $errores[] = $this->error(
                        $importacion->id,
                        $fila,
                        null,
                        $exception->getMessage(),
                    );
                }

                if ($estadisticas['total'] % $tamanoLote === 0) {
                    $this->procesarLote(
                        $lote,
                        $errores,
                        (string) $periodo,
                        (string) $empresa,
                        $estadisticas,
                        $clavesRecuperadas,
                    );
                    $lote = [];
                    $errores = [];
                    $this->actualizarProgreso($importacion, $estadisticas);
                }
            }

            if ($lote !== [] || $errores !== []) {
                $this->procesarLote(
                    $lote,
                    $errores,
                    (string) $periodo,
                    (string) $empresa,
                    $estadisticas,
                    $clavesRecuperadas,
                );
            }

            $this->actualizarProgreso($importacion, $estadisticas);
            $this->completar($importacion, $estadisticas, [
                'periodo' => $periodo,
                'empresa' => $empresa,
                'total_archivo' => $inspeccion['total_filas_detectadas'] ?? $estadisticas['total'],
            ]);
        } catch (\Throwable $exception) {
            $this->marcarFallo($importacion, $exception, $estadisticas);
            throw $exception;
        } finally {
            $this->limpiarTemporal($contexto['ruta_temporal']);
        }

        return [
            'duplicado' => false,
            'importacion' => $importacion->fresh(),
        ];
    }

    private function procesarLote(
        array $lote,
        array $errores,
        string $periodo,
        string $empresa,
        array &$estadisticas,
        array &$clavesRecuperadas,
    ): void {
        $estadisticas['lotes']++;

        DB::transaction(function () use (
            $lote,
            $errores,
            $periodo,
            $empresa,
            &$estadisticas,
            &$clavesRecuperadas,
        ): void {
            $existentes = collect();
            if ($lote !== []) {
                $existentes = AsignacionExpertis::query()
                    ->where('periodo', $periodo)
                    ->where('empresa', $empresa)
                    ->whereIn(
                        'codigo_normalizado',
                        array_values(array_unique(array_column($lote, 'codigo_normalizado'))),
                    )
                    ->get(['id', 'codigo_normalizado', 'hash_fila'])
                    ->keyBy('codigo_normalizado');
            }

            $escrituras = [];
            foreach ($lote as $asignacion) {
                $clave = $this->clave($asignacion);
                $existente = $existentes->get($asignacion['codigo_normalizado']);
                $recuperada = array_key_exists($clave, $clavesRecuperadas);

                if ($existente && hash_equals($existente->hash_fila, $asignacion['hash_fila'])) {
                    $estadisticas[$recuperada ? 'insertadas' : 'duplicadas']++;
                    unset($clavesRecuperadas[$clave]);

                    continue;
                }

                $escrituras[] = $asignacion;
                $estadisticas[$existente && ! $recuperada ? 'actualizadas' : 'insertadas']++;
                unset($clavesRecuperadas[$clave]);
            }

            if ($escrituras !== []) {
                DB::table('asignaciones_expertis')->upsert(
                    $escrituras,
                    ['periodo', 'empresa', 'codigo_normalizado'],
                    self::CAMPOS_ACTUALIZABLES,
                );
            }

            if ($errores !== []) {
                DB::table('errores_importacion_expertis')->insert($errores);
            }
        }, 3);
    }

    private function clave(array $asignacion): string
    {
        return implode('|', [
            $asignacion['periodo'] ?? '',
            $asignacion['empresa'] ?? '',
            $asignacion['codigo_normalizado'] ?? '',
        ]);
    }

    private function error(
        int $importacionId,
        array $fila,
        ?string $campo,
        string $mensaje,
    ): array {
        return [
            'importacion_expertis_id' => $importacionId,
            'numero_fila' => $fila['numero_fila'],
            'campo' => $campo,
            'mensaje' => $mensaje,
            'datos_originales' => json_encode(
                $fila['originales'],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
