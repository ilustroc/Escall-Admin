<?php

namespace App\Services\Cargas;

use App\Models\Pago;
use App\Support\CapitalRange;

class RegistrarPagoService
{
    public function register(array $data): Pago
    {
        if (empty($data['rango'])) {
            $data['rango'] = CapitalRange::from($data['capital'] ?? null);
        }

        return Pago::query()->create($data);
    }
}
