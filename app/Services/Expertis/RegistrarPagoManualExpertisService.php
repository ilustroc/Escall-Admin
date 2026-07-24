<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use App\Models\PagoExpertis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrarPagoManualExpertisService
{
    public function __construct(
        private readonly PagoExpertisDataMapper $mapper,
    ) {}

    public function registrar(array $datos, int $userId): array
    {
        $pago = $this->mapper->map($datos);

        return DB::transaction(function () use ($pago, $userId): array {
            $inicio = now();
            $importacion = ImportacionExpertis::create([
                'user_id' => $userId,
                'tipo' => ImportacionExpertis::TIPO_PAGOS,
                'nombre_original' => 'Registro manual · '.$pago['cuenta'],
                'nombre_guardado' => '',
                'ruta_archivo' => '',
                'hash_archivo' => hash('sha256', 'manual:'.Str::uuid()),
                'estado' => ImportacionExpertis::ESTADO_PROCESANDO,
                'iniciado_at' => $inicio,
            ]);

            $pago['importacion_expertis_id'] = $importacion->id;
            $insertado = DB::table('pagos_expertis')->insertOrIgnore($pago) === 1;
            $duplicado = ! $insertado;
            $estadisticas = [
                'total' => 1,
                'insertadas' => $insertado ? 1 : 0,
                'actualizadas' => 0,
                'duplicadas' => $duplicado ? 1 : 0,
                'errores' => 0,
                'fecha_minima' => $pago['fecha'],
                'fecha_maxima' => $pago['fecha'],
                'origen' => 'manual',
            ];

            $importacion->update([
                'estado' => $duplicado
                    ? ImportacionExpertis::ESTADO_DUPLICADO
                    : ImportacionExpertis::ESTADO_COMPLETADO,
                'total_filas' => 1,
                'filas_insertadas' => $insertado ? 1 : 0,
                'filas_actualizadas' => 0,
                'filas_duplicadas' => $duplicado ? 1 : 0,
                'filas_error' => 0,
                'fecha_minima' => $pago['fecha'],
                'fecha_maxima' => $pago['fecha'],
                'finalizado_at' => now(),
                'resumen' => $estadisticas,
                'mensaje_error' => null,
            ]);

            return [
                'duplicado' => $duplicado,
                'importacion' => $importacion->fresh(),
                'pago' => PagoExpertis::query()
                    ->where('hash_fila', $pago['hash_fila'])
                    ->firstOrFail(),
            ];
        }, 3);
    }
}
