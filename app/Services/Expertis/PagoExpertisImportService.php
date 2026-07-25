<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use App\Models\PagoExpertis;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PagoExpertisImportService extends AbstractExpertisImportService
{
    public function __construct(
        ExpertisSpreadsheetService $spreadsheet,
        NormalizadorExpertisService $normalizador,
        private readonly PagoExpertisDataMapper $mapper,
    ) {
        parent::__construct($spreadsheet, $normalizador);
    }

    public function importar(
        string $rutaTemporal,
        string $nombreOriginal,
        ?int $userId,
    ): array {
        $this->prepararEjecucionLarga();

        $rutaTemporalAbsoluta = \Storage::disk('local')->path($rutaTemporal);
        $inspeccion = $this->spreadsheet->inspeccionar(
            $rutaTemporalAbsoluta,
            ExpertisSpreadsheetService::PAGOS,
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
            ImportacionExpertis::TIPO_PAGOS,
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
        ];
        $lote = [];
        $errores = [];
        $tamanoLote = max(1, (int) config('expertis.chunk_size', 1000));
        $hashesRecuperados = PagoExpertis::query()
            ->where('importacion_expertis_id', $importacion->id)
            ->pluck('hash_fila')
            ->flip()
            ->all();

        $importacion->errores()->delete();

        DB::connection()->disableQueryLog();

        try {
            foreach ($this->spreadsheet->filas(
                $contexto['ruta_proceso'],
                ExpertisSpreadsheetService::PAGOS,
            ) as $fila) {
                $estadisticas['total']++;

                try {
                    $pago = $this->mapper->map(
                        $fila['datos'],
                        $importacion->id,
                        $fila['numero_fila'],
                    );
                    $this->actualizarRango($estadisticas, $pago['fecha']);
                    $lote[] = $pago;
                } catch (\InvalidArgumentException $e) {
                    $estadisticas['errores']++;
                    $errores[] = [
                        'importacion_expertis_id' => $importacion->id,
                        'numero_fila' => $fila['numero_fila'],
                        'campo' => null,
                        'mensaje' => $e->getMessage(),
                        'datos_originales' => json_encode(
                            $fila['originales'],
                            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
                        ),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($estadisticas['total'] % $tamanoLote === 0) {
                    if ($lote !== []) {
                        $this->procesarLote(
                            $lote,
                            $estadisticas,
                            $hashesRecuperados,
                        );
                    }
                    $lote = [];

                    if ($errores !== []) {
                        DB::table('errores_importacion_expertis')->insert($errores);
                    }
                    $errores = [];

                    $this->actualizarProgreso($importacion, $estadisticas);
                }
            }

            if ($lote !== []) {
                $this->procesarLote(
                    $lote,
                    $estadisticas,
                    $hashesRecuperados,
                );
            }

            if ($errores !== []) {
                DB::table('errores_importacion_expertis')->insert($errores);
            }

            $this->actualizarProgreso($importacion, $estadisticas);
            $this->completar($importacion, $estadisticas);
        } catch (\Throwable $e) {
            $this->marcarFallo($importacion, $e, $estadisticas);
            throw $e;
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
        array &$estadisticas,
        array &$hashesRecuperados,
    ): void {
        DB::transaction(function () use (
            $lote,
            &$estadisticas,
            &$hashesRecuperados,
        ): void {
            $unicos = [];
            foreach ($lote as $fila) {
                if (isset($unicos[$fila['hash_fila']])) {
                    $estadisticas['duplicadas']++;

                    continue;
                }
                $unicos[$fila['hash_fila']] = $fila;
            }

            $hashesExistentes = PagoExpertis::query()
                ->whereIn('hash_fila', array_keys($unicos))
                ->pluck('importacion_expertis_id', 'hash_fila');

            $nuevos = [];
            foreach ($unicos as $hash => $fila) {
                if ($hashesExistentes->has($hash)) {
                    $esFilaRecuperada = isset($hashesRecuperados[$hash]);
                    if ($esFilaRecuperada) {
                        unset($hashesRecuperados[$hash]);
                    }

                    $estadisticas[
                        $esFilaRecuperada ? 'insertadas' : 'duplicadas'
                    ]++;
                } else {
                    $nuevos[] = $fila;
                }
            }

            if ($nuevos !== []) {
                $insertadas = DB::table('pagos_expertis')->insertOrIgnore($nuevos);
                $estadisticas['insertadas'] += $insertadas;
                $estadisticas['duplicadas'] += count($nuevos) - $insertadas;
            }
        }, 3);
    }
}
