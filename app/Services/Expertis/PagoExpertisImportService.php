<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use App\Models\PagoExpertis;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PagoExpertisImportService extends AbstractExpertisImportService
{
    public function importar(
        string $rutaTemporal,
        string $nombreOriginal,
        ?int $userId,
    ): array {
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
        $tamanoLote = (int) config('expertis.chunk_size', 1000);

        DB::connection()->disableQueryLog();

        try {
            foreach ($this->spreadsheet->filas(
                $contexto['ruta_proceso'],
                ExpertisSpreadsheetService::PAGOS,
            ) as $fila) {
                $estadisticas['total']++;

                try {
                    $pago = $this->normalizarFila(
                        $fila['datos'],
                        $fila['numero_fila'],
                        $importacion->id,
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

                if (count($lote) >= $tamanoLote) {
                    $this->procesarLote($lote, $estadisticas);
                    $lote = [];
                }

                if (count($errores) >= $tamanoLote) {
                    DB::table('errores_importacion_expertis')->insert($errores);
                    $errores = [];
                }
            }

            if ($lote !== []) {
                $this->procesarLote($lote, $estadisticas);
            }

            if ($errores !== []) {
                DB::table('errores_importacion_expertis')->insert($errores);
            }

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

    private function normalizarFila(array $fila, int $numeroFila, int $importacionId): array
    {
        $fecha = $this->normalizador->fecha($fila['fecha'] ?? null);
        if (! $fecha) {
            throw new \InvalidArgumentException('La fecha no tiene un formato válido.');
        }

        $cuenta = $this->normalizador->cuentaVisible($fila['cuenta'] ?? null);
        if ($cuenta === '') {
            throw new \InvalidArgumentException('La cuenta es obligatoria.');
        }

        $monto = $this->normalizador->monto($fila['monto'] ?? null);
        if ($monto === null || (float) $monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser un número mayor que cero.');
        }

        [$documento] = explode('-', $cuenta, 2);
        $pago = [
            'importacion_expertis_id' => $importacionId,
            'fecha' => $fecha,
            'cuenta' => $cuenta,
            'cuenta_normalizada' => $this->normalizador->codigoNormalizado($cuenta),
            'dni' => $this->normalizador->dni($documento) ?: null,
            'monto' => $monto,
            'ejecutivo' => $this->normalizador->texto($fila['ejecutivo'] ?? null, 150),
            'tipo_acuerdo' => $this->normalizador->texto($fila['tipo_acuerdo'] ?? null, 100),
            'recaudo' => $this->normalizador->texto($fila['recaudo'] ?? null, 50),
            'numero_fila_origen' => $numeroFila,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $pago['hash_fila'] = $this->normalizador->hashPago($pago);

        return $pago;
    }

    private function procesarLote(array $lote, array &$estadisticas): void
    {
        DB::transaction(function () use ($lote, &$estadisticas): void {
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
                ->pluck('hash_fila')
                ->flip();

            $nuevos = [];
            foreach ($unicos as $hash => $fila) {
                if ($hashesExistentes->has($hash)) {
                    $estadisticas['duplicadas']++;
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
