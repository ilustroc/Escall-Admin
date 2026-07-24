<?php

namespace App\Services\Expertis;

use App\Models\GestionExpertis;
use App\Models\ImportacionExpertis;
use App\Models\TipificacionExpertis;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GestionExpertisImportService extends AbstractExpertisImportService
{
    private const ACTUALIZABLES = [
        'nombre_cliente',
        'fecha_compromiso',
        'monto',
        'observacion',
        'asesor',
        'equipo',
        'campania',
        'medio_gestion',
    ];

    public function importar(
        string $rutaTemporal,
        string $nombreOriginal,
        ?int $userId,
    ): array {
        $rutaTemporalAbsoluta = \Storage::disk('local')->path($rutaTemporal);
        $inspeccion = $this->spreadsheet->inspeccionar(
            $rutaTemporalAbsoluta,
            ExpertisSpreadsheetService::GESTIONES,
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
            ImportacionExpertis::TIPO_GESTIONES,
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
        $tipificacionesDesconocidas = [];
        $lote = [];
        $errores = [];
        $tamanoLote = (int) config('expertis.chunk_size', 1000);

        DB::connection()->disableQueryLog();
        $tipificaciones = TipificacionExpertis::query()
            ->pluck('id', 'tipificacion')
            ->mapWithKeys(fn ($id, $clave) => [mb_strtoupper(trim($clave), 'UTF-8') => $id])
            ->all();

        try {
            foreach ($this->spreadsheet->filas(
                $contexto['ruta_proceso'],
                ExpertisSpreadsheetService::GESTIONES,
            ) as $fila) {
                $estadisticas['total']++;

                try {
                    $gestion = $this->normalizarFila(
                        $fila['datos'],
                        $fila['numero_fila'],
                        $importacion->id,
                        $tipificaciones,
                        $tipificacionesDesconocidas,
                    );
                    $this->actualizarRango($estadisticas, $gestion['fecha_llamada']);
                    $lote[] = $gestion;
                } catch (\InvalidArgumentException $e) {
                    $estadisticas['errores']++;
                    $errores[] = [
                        'importacion_expertis_id' => $importacion->id,
                        'numero_fila' => $fila['numero_fila'],
                        'campo' => $e->getCode() > 0 ? (string) $e->getCode() : null,
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

            $desconocidas = array_keys($tipificacionesDesconocidas);
            sort($desconocidas);
            $this->completar($importacion, $estadisticas, [
                'tipificaciones_desconocidas' => $desconocidas,
            ]);
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

    private function normalizarFila(
        array $fila,
        int $numeroFila,
        int $importacionId,
        array $tipificaciones,
        array &$desconocidas,
    ): array {
        $dni = $this->normalizador->dni($fila['dni'] ?? null);
        if ($dni === '') {
            throw new \InvalidArgumentException('El DNI está vacío o no contiene dígitos.');
        }

        $cartera = $this->normalizador->cartera($fila['cartera'] ?? null);
        if ($cartera === '') {
            throw new \InvalidArgumentException('La cartera es obligatoria.');
        }

        $fecha = $this->normalizador->fecha($fila['fecha_llamada'] ?? null);
        if (! $fecha) {
            throw new \InvalidArgumentException('La Fecha Llamada no tiene un formato válido.');
        }

        $nivel2 = $this->normalizador->texto($fila['nivel_2'] ?? null, 60);
        if (! $nivel2) {
            throw new \InvalidArgumentException('Nivel 2 es obligatorio.');
        }

        $horaOriginal = $fila['hora'] ?? null;
        $hora = $this->normalizador->hora($horaOriginal);
        if ($this->tieneValor($horaOriginal) && ! $hora) {
            throw new \InvalidArgumentException('La hora no tiene un formato válido.');
        }

        $fechaCompromisoOriginal = $fila['fecha_compromiso'] ?? null;
        $fechaCompromiso = $this->normalizador->fecha($fechaCompromisoOriginal);
        if (
            $this->tieneValor($fechaCompromisoOriginal)
            && ! $fechaCompromiso
        ) {
            throw new \InvalidArgumentException('La Fecha Compromiso no tiene un formato válido.');
        }

        $montoOriginal = $fila['monto'] ?? null;
        $monto = $this->normalizador->monto($montoOriginal);
        if ($this->tieneValor($montoOriginal) && $monto === null) {
            throw new \InvalidArgumentException('El monto no tiene un formato válido.');
        }

        $codigo = $this->normalizador->codigoVisible($dni, $cartera);
        $gestion = [
            'importacion_expertis_id' => $importacionId,
            'codigo' => $codigo,
            'codigo_normalizado' => $this->normalizador->codigoNormalizado($codigo),
            'canal_gestion' => $this->normalizador->texto($fila['canal_gestion'] ?? null, 100),
            'canal_asignacion' => $this->normalizador->texto($fila['canal_asignacion'] ?? null, 100),
            'dni' => $dni,
            'nombre_cliente' => $this->normalizador->textoLibre($fila['nombre_cliente'] ?? null, 180),
            'cartera' => $cartera,
            'asesor' => $this->normalizador->texto($fila['asesor'] ?? null, 150),
            'equipo' => $this->normalizador->texto($fila['equipo'] ?? null, 150),
            'telefono' => $this->normalizador->telefono($fila['telefono'] ?? null),
            'fecha_llamada' => $fecha,
            'hora' => $hora,
            'fecha_hora' => $fecha.' '.($hora ?? '00:00:00'),
            'campania' => $this->normalizador->texto($fila['campania'] ?? null, 191),
            'nivel_1' => $this->normalizador->texto($fila['nivel_1'] ?? null, 120),
            'nivel_2' => $nivel2,
            'tipificacion_expertis_id' => $tipificaciones[$nivel2] ?? null,
            'fecha_compromiso' => $fechaCompromiso,
            'monto' => $monto ?? '0.00',
            'observacion' => $this->normalizador->textoLibre($fila['observacion'] ?? null),
            'medio_gestion' => $this->normalizador->texto($fila['medio_gestion'] ?? null, 100),
            'numero_fila_origen' => $numeroFila,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $gestion['hash_fila'] = $this->normalizador->hashGestion($gestion);

        if ($gestion['tipificacion_expertis_id'] === null) {
            $desconocidas[$nivel2] = true;
        }

        return $gestion;
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

            $existentes = GestionExpertis::query()
                ->whereIn('hash_fila', array_keys($unicos))
                ->get()
                ->keyBy('hash_fila');
            $sinCoincidenciaExacta = collect($unicos)
                ->reject(fn (array $fila, string $hash) => $existentes->has($hash));
            $candidatosEnriquecibles = collect();

            if ($sinCoincidenciaExacta->isNotEmpty()) {
                $candidatosEnriquecibles = GestionExpertis::query()
                    ->whereIn(
                        'codigo_normalizado',
                        $sinCoincidenciaExacta->pluck('codigo_normalizado')->unique()->values(),
                    )
                    ->whereIn(
                        'fecha_llamada',
                        $sinCoincidenciaExacta->pluck('fecha_llamada')->unique()->values(),
                    )
                    ->get()
                    ->groupBy(fn (GestionExpertis $gestion) => $this->identidadEnriquecimiento($gestion));
            }

            $nuevos = [];
            $actualizaciones = [];
            $candidatosUsados = [];

            foreach ($unicos as $hash => $fila) {
                /** @var GestionExpertis|null $existente */
                $existente = $existentes->get($hash);
                if (! $existente) {
                    $candidatos = $candidatosEnriquecibles
                        ->get($this->identidadEnriquecimiento($fila), collect())
                        ->reject(fn (GestionExpertis $gestion) => isset($candidatosUsados[$gestion->id]))
                        ->filter(fn (GestionExpertis $gestion) => $this->puedeEnriquecer($gestion, $fila))
                        ->values();

                    if ($candidatos->count() !== 1) {
                        $nuevos[] = $fila;

                        continue;
                    }

                    $existente = $candidatos->first();
                    $candidatosUsados[$existente->id] = true;
                }

                $cambios = $this->camposMasCompletos($existente, $fila);
                if ($cambios === []) {
                    $estadisticas['duplicadas']++;

                    continue;
                }

                $actualizaciones[] = array_merge(
                    $existente->getAttributes(),
                    $cambios,
                    ['hash_fila' => $hash],
                    ['updated_at' => now()],
                );
                $estadisticas['actualizadas']++;
            }

            if ($nuevos !== []) {
                $insertadas = DB::table('gestiones_expertis')->insertOrIgnore($nuevos);
                $estadisticas['insertadas'] += $insertadas;
                $estadisticas['duplicadas'] += count($nuevos) - $insertadas;
            }

            if ($actualizaciones !== []) {
                DB::table('gestiones_expertis')->upsert(
                    $actualizaciones,
                    ['id'],
                    array_merge(self::ACTUALIZABLES, ['hash_fila', 'updated_at']),
                );
            }
        }, 3);
    }

    private function identidadEnriquecimiento(GestionExpertis|array $gestion): string
    {
        $valor = function (string $campo) use ($gestion): mixed {
            if ($gestion instanceof GestionExpertis) {
                return $gestion->getRawOriginal($campo);
            }

            return $gestion[$campo] ?? null;
        };

        return hash('sha256', json_encode([
            $valor('codigo_normalizado') ?? '',
            $valor('telefono') ?? '',
            $valor('fecha_llamada') ?? '',
            $valor('hora') ?? '00:00:00',
            $this->normalizador->texto($valor('nivel_1')) ?? '',
            $this->normalizador->texto($valor('nivel_2')) ?? '',
        ], JSON_UNESCAPED_UNICODE));
    }

    private function puedeEnriquecer(GestionExpertis $existente, array $nuevo): bool
    {
        $hayEnriquecimiento = false;

        foreach (['asesor', 'campania', 'medio_gestion'] as $campo) {
            $actual = $this->normalizador->texto($existente->{$campo});
            $entrante = $this->normalizador->texto($nuevo[$campo] ?? null);

            if ($actual !== null && $entrante !== null && $actual !== $entrante) {
                return false;
            }

            if ($actual === null && $entrante !== null) {
                $hayEnriquecimiento = true;
            }
        }

        return $hayEnriquecimiento;
    }

    private function camposMasCompletos(GestionExpertis $existente, array $nuevo): array
    {
        $cambios = [];
        foreach (self::ACTUALIZABLES as $campo) {
            $actual = $existente->{$campo};
            $entrante = $nuevo[$campo] ?? null;

            if ($entrante === null || $entrante === '') {
                continue;
            }

            if ($campo === 'monto') {
                if ((float) $actual <= 0 && (float) $entrante > 0) {
                    $cambios[$campo] = $entrante;
                }

                continue;
            }

            if ($campo === 'observacion') {
                if (mb_strlen((string) $entrante) > mb_strlen((string) $actual)) {
                    $cambios[$campo] = $entrante;
                }

                continue;
            }

            if ($actual === null || trim((string) $actual) === '') {
                $cambios[$campo] = $entrante;
            }
        }

        return $cambios;
    }

    private function tieneValor(mixed $valor): bool
    {
        if ($valor === null) {
            return false;
        }

        if ($valor instanceof \DateTimeInterface) {
            return true;
        }

        return trim((string) $valor) !== '';
    }
}
