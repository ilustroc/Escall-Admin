<?php

namespace App\Queries\Tablas;

use App\Models\Data;
use Illuminate\Database\Eloquent\Builder;

class DataTableQuery
{
    public function get(array $filters)
    {
        $query = Data::query()->select([
            'codigo',
            'dni',
            'titular',
            'cartera',
            'entidad',
            'cosecha',
            'producto',
            'departamento',
            'deuda_total',
            'deuda_capital',
            'porcentaje',
        ]);

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('codigo', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('titular', 'like', "%{$search}%");
            });
        }

        if (filled($filters['cartera'] ?? null)) {
            $query->where('cartera', $filters['cartera']);
        }

        $allowed = [
            'codigo' => 'codigo',
            'dni' => 'dni',
            'titular' => 'titular',
            'cartera' => 'cartera',
            'deuda_capital' => 'deuda_capital',
        ];
        $sort = $allowed[$filters['sort'] ?? 'codigo'] ?? 'codigo';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        return [
            'detalle' => $query->orderBy($sort, $direction)
                ->paginate((int) $filters['per_page'])
                ->withQueryString(),
            'carteras' => Data::query()
                ->whereNotNull('cartera')
                ->distinct()
                ->orderBy('cartera')
                ->pluck('cartera'),
        ];
    }
}
