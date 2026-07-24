<?php

namespace App\Queries\Tablas;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Builder;

class PagosTableQuery
{
    private const WALLETS = ['KP INVEST', 'TEC CENTER', 'IMPULSE'];

    public function get(array $filters): array
    {
        [$year, $month] = array_map('intval', explode('-', $filters['mes']));
        $raw = Pago::query()
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->selectRaw("COALESCE(asesor, 'SIN ASIGNAR') as asesor")
            ->selectRaw("COALESCE(cartera, 'OTRAS') as cartera")
            ->selectRaw('SUM(monto) as monto, COUNT(*) as ops')
            ->groupBy('asesor', 'cartera')
            ->get();

        $matrix = [];
        foreach ($raw as $row) {
            $agent = $row->asesor;
            $wallet = mb_strtoupper((string) $row->cartera, 'UTF-8');
            $matrix[$agent] ??= [
                'asesor' => $agent,
                'carteras' => [],
                'total_monto' => 0.0,
                'total_ops' => 0,
            ];
            $matrix[$agent]['carteras'][$wallet] = [
                'monto' => (float) $row->monto,
                'ops' => (int) $row->ops,
            ];
            $matrix[$agent]['total_monto'] += (float) $row->monto;
            $matrix[$agent]['total_ops'] += (int) $row->ops;
        }
        uasort($matrix, fn (array $a, array $b) => $b['total_monto'] <=> $a['total_monto']);

        $footer = [
            'carteras' => collect(self::WALLETS)
                ->mapWithKeys(fn (string $wallet) => [$wallet => ['monto' => 0.0, 'ops' => 0]])
                ->all(),
            'grand_monto' => 0.0,
            'grand_ops' => 0,
        ];
        foreach ($raw as $row) {
            $wallet = mb_strtoupper((string) $row->cartera, 'UTF-8');
            if (isset($footer['carteras'][$wallet])) {
                $footer['carteras'][$wallet]['monto'] += (float) $row->monto;
                $footer['carteras'][$wallet]['ops'] += (int) $row->ops;
            }
            $footer['grand_monto'] += (float) $row->monto;
            $footer['grand_ops'] += (int) $row->ops;
        }

        return [
            'mes' => $filters['mes'],
            'carteras' => self::WALLETS,
            'matriz' => array_values($matrix),
            'totales' => $footer,
            'detalle' => $this->details($filters, $year, $month),
        ];
    }

    private function details(array $filters, int $year, int $month)
    {
        $query = Pago::query()
            ->select([
                'id',
                'fecha',
                'codigo',
                'dni',
                'nombre',
                'cartera',
                'monto',
                'asesor',
                'operacion',
            ])
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month);

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('codigo', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('asesor', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if (filled($filters['cartera'] ?? null)) {
            $query->where('cartera', $filters['cartera']);
        }

        $allowed = [
            'fecha' => 'fecha',
            'monto' => 'monto',
            'codigo' => 'codigo',
            'asesor' => 'asesor',
            'cartera' => 'cartera',
        ];
        $sort = $allowed[$filters['sort'] ?? 'fecha'] ?? 'fecha';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate((int) $filters['per_page'])
            ->withQueryString();
    }
}
