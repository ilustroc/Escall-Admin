<?php

namespace App\Services\Expertis;

class PagoExpertisDataMapper
{
    public function __construct(
        private readonly NormalizadorExpertisService $normalizador,
    ) {}

    public function map(
        array $fila,
        ?int $importacionId = null,
        ?int $numeroFila = null,
    ): array {
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
}
