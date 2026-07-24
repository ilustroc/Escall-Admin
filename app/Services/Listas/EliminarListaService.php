<?php

namespace App\Services\Listas;

use App\Models\Data;
use App\Models\Gestion;
use App\Models\Pago;

class EliminarListaService
{
    public function delete(string $type, mixed $id): void
    {
        match ($type) {
            'pago' => Pago::query()->findOrFail($id)->delete(),
            'gestion' => Gestion::query()->findOrFail($id)->delete(),
            'data' => Data::query()->findOrFail($id)->delete(),
        };
    }
}
