<?php

namespace App\Queries\Listas;

use App\Models\Data;
use App\Models\Gestion;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListasQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->builder($filters)
            ->paginate((int) $filters['per_page'])
            ->withQueryString();
    }

    public function builder(array $filters): Builder
    {
        return match ($filters['tab']) {
            'pagos' => $this->payments($filters),
            'gestiones' => $this->managements($filters),
            'data' => $this->portfolio($filters),
        };
    }

    public function options(): array
    {
        return [
            'asesores' => Pago::query()
                ->whereNotNull('asesor')
                ->where('asesor', '<>', '')
                ->distinct()
                ->orderBy('asesor')
                ->pluck('asesor'),
            'cosechas' => Data::query()
                ->whereNotNull('cosecha')
                ->where('cosecha', '<>', '')
                ->distinct()
                ->orderBy('cosecha')
                ->pluck('cosecha'),
            'carteras' => Data::query()
                ->whereNotNull('cartera')
                ->where('cartera', '<>', '')
                ->distinct()
                ->orderBy('cartera')
                ->pluck('cartera'),
            'resultados' => Gestion::query()
                ->whereNotNull('tipificacion')
                ->where('tipificacion', '<>', '')
                ->distinct()
                ->orderBy('tipificacion')
                ->pluck('tipificacion'),
        ];
    }

    private function payments(array $filters): Builder
    {
        $query = Pago::query()->select([
            'id',
            'codigo',
            'dni',
            'nombre',
            'cartera',
            'cosecha',
            'fecha',
            'monto',
            'asesor',
            'operacion',
        ]);

        $this->contains($query, 'dni', $filters['dni'] ?? null);
        $this->dateRange($query, 'fecha', $filters);
        $this->equals($query, 'cosecha', $filters['cosecha'] ?? null);
        $this->equals($query, 'asesor', $filters['asesor'] ?? null);

        return $this->order($query, $filters, [
            'fecha' => 'fecha',
            'monto' => 'monto',
            'dni' => 'dni',
            'asesor' => 'asesor',
        ], 'fecha');
    }

    private function managements(array $filters): Builder
    {
        $query = Gestion::query()->select([
            'id',
            'dni',
            'telefono',
            'fecha_gestion',
            'status',
            'tipificacion',
            'observacion',
            'nombre',
        ]);

        $this->contains($query, 'dni', $filters['dni'] ?? null);
        $this->dateRange($query, 'fecha_gestion', $filters);
        $this->equals($query, 'tipificacion', $filters['resultado'] ?? null);
        $this->equals($query, 'nombre', $filters['asesor'] ?? null);

        $hasUserFilter = collect(['dni', 'fecha_ini', 'fecha_fin', 'resultado', 'asesor'])
            ->contains(fn (string $key) => filled($filters[$key] ?? null));

        if (! $hasUserFilter) {
            $query->whereYear('fecha_gestion', now()->year)
                ->whereMonth('fecha_gestion', now()->month);
        }

        return $this->order($query, $filters, [
            'fecha_gestion' => 'fecha_gestion',
            'dni' => 'dni',
            'tipificacion' => 'tipificacion',
            'nombre' => 'nombre',
        ], 'fecha_gestion');
    }

    private function portfolio(array $filters): Builder
    {
        $query = Data::query()->select([
            'codigo',
            'dni',
            'titular',
            'cartera',
            'cosecha',
            'deuda_capital',
            'producto',
            'departamento',
        ]);

        $this->contains($query, 'dni', $filters['dni'] ?? null);
        $this->equals($query, 'cartera', $filters['cartera'] ?? null);
        $this->equals($query, 'cosecha', $filters['cosecha'] ?? null);

        return $this->order($query, $filters, [
            'codigo' => 'codigo',
            'dni' => 'dni',
            'titular' => 'titular',
            'cartera' => 'cartera',
            'deuda_capital' => 'deuda_capital',
        ], 'codigo');
    }

    private function contains(Builder $query, string $column, mixed $value): void
    {
        if (filled($value)) {
            $query->where($column, 'like', '%'.trim((string) $value).'%');
        }
    }

    private function equals(Builder $query, string $column, mixed $value): void
    {
        if (filled($value)) {
            $query->where($column, $value);
        }
    }

    private function dateRange(Builder $query, string $column, array $filters): void
    {
        if (filled($filters['fecha_ini'] ?? null)) {
            $query->whereDate($column, '>=', $filters['fecha_ini']);
        }
        if (filled($filters['fecha_fin'] ?? null)) {
            $query->whereDate($column, '<=', $filters['fecha_fin']);
        }
    }

    private function order(
        Builder $query,
        array $filters,
        array $allowed,
        string $default,
    ): Builder {
        $column = $allowed[$filters['sort'] ?? $default] ?? $default;
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction);
    }
}
