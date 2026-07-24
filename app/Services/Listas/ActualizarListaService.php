<?php

namespace App\Services\Listas;

use App\Models\Data;
use App\Models\Gestion;
use App\Models\Pago;

class ActualizarListaService
{
    public function update(array $data): void
    {
        match ($data['type']) {
            'pago' => $this->updatePago($data),
            'gestion' => $this->updateGestion($data),
            'data' => $this->updateData($data),
        };
    }

    private function updatePago(array $data): void
    {
        Pago::query()->findOrFail($data['id'])->update(array_filter([
            'fecha' => $data['fecha'] ?? null,
            'monto' => $data['monto'] ?? null,
            'cosecha' => $data['cosecha'] ?? null,
            'asesor' => $data['asesor'] ?? null,
            'operacion' => $data['operacion'] ?? null,
        ], fn (mixed $value) => $value !== null));
    }

    private function updateGestion(array $data): void
    {
        Gestion::query()->findOrFail($data['id'])->update(array_filter([
            'tipificacion' => $data['resultado'] ?? null,
            'observacion' => $data['observacion'] ?? null,
            'nombre' => $data['asesor'] ?? null,
            'fecha_gestion' => $data['fecha_gestion'] ?? null,
        ], fn (mixed $value) => $value !== null));
    }

    private function updateData(array $data): void
    {
        Data::query()->findOrFail($data['id'])->update(array_filter([
            'cartera' => $data['cartera'] ?? null,
            'cosecha' => $data['cosecha'] ?? null,
            'titular' => $data['titular'] ?? null,
            'deuda_capital' => $data['deuda_capital'] ?? null,
        ], fn (mixed $value) => $value !== null));
    }
}
