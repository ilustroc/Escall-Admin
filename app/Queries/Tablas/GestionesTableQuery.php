<?php

namespace App\Queries\Tablas;

use App\Models\Gestion;
use App\Support\CapitalRange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GestionesTableQuery
{
    public function get(array $filters): array
    {
        $month = $filters['mes'];
        [$year, $monthNumber] = array_map('intval', explode('-', $month));
        $start = Carbon::create($year, $monthNumber, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $days = collect(range(1, $start->daysInMonth))
            ->map(fn (int $day) => $start->copy()->day($day)->toDateString())
            ->all();

        DB::connection()->disableQueryLog();
        $rows = DB::table('gestiones')
            ->selectRaw("COALESCE(NULLIF(TRIM(nombre), ''), 'SIN NOMBRE') AS agente")
            ->selectRaw('DATE(fecha_gestion) AS dia')
            ->selectRaw('COUNT(*) AS total')
            ->whereBetween('fecha_gestion', [$start->toDateString(), $end->toDateString()])
            ->groupBy('agente', DB::raw('DATE(fecha_gestion)'))
            ->get();

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row->agente][$row->dia] = (int) $row->total;
        }

        $totalsByDay = array_fill_keys($days, 0);
        foreach ($matrix as $values) {
            foreach ($days as $day) {
                $totalsByDay[$day] += $values[$day] ?? 0;
            }
        }

        uasort($matrix, fn (array $a, array $b) => array_sum($b) <=> array_sum($a));

        return [
            'mes' => $month,
            'dias' => $days,
            'matriz' => collect($matrix)->map(fn (array $values, string $agent) => [
                'agente' => $agent,
                'dias' => $values,
                'total' => array_sum($values),
            ])->values(),
            'totales_dia' => $totalsByDay,
            'total_general' => array_sum($totalsByDay),
            'semana' => $this->weekly($filters['week']),
            'detalle' => $this->details($filters, $start, $end),
        ];
    }

    private function details(array $filters, Carbon $start, Carbon $end)
    {
        $query = Gestion::query()
            ->select([
                'id',
                'fecha_gestion',
                'dni',
                'telefono',
                'status',
                'tipificacion',
                'nombre',
                'observacion',
            ])
            ->whereBetween('fecha_gestion', [$start->toDateString(), $end->toDateString()]);

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('dni', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('tipificacion', 'like', "%{$search}%");
            });
        }

        $allowed = [
            'fecha_gestion' => 'fecha_gestion',
            'dni' => 'dni',
            'nombre' => 'nombre',
            'tipificacion' => 'tipificacion',
        ];
        $sort = $allowed[$filters['sort'] ?? 'fecha_gestion'] ?? 'fecha_gestion';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate((int) $filters['per_page'])
            ->withQueryString();
    }

    private function weekly(string $week): array
    {
        [$isoYear, $isoWeek] = sscanf($week, '%d-W%d');
        $monday = Carbon::now()->setISODate((int) $isoYear, (int) $isoWeek)->startOfDay();
        $days = [];
        $labels = [];

        for ($index = 0; $index < 5; $index++) {
            $date = $monday->copy()->addDays($index);
            $days[] = $date->toDateString();
            $labels[] = ucfirst($date->locale('es')->translatedFormat('l j'));
        }

        $rangeSql = "CASE
            WHEN d.deuda_capital >= 50000 THEN '1.[50K - MAS]'
            WHEN d.deuda_capital >= 20000 THEN '2.[20K - 50K]'
            WHEN d.deuda_capital >= 10000 THEN '3.[10K - 20K]'
            WHEN d.deuda_capital >= 5000 THEN '4.[5K - 10K]'
            WHEN d.deuda_capital >= 1000 THEN '5.[1K - 5K]'
            WHEN d.deuda_capital >= 501 THEN '6.[501 - 1K]'
            ELSE '7.[0 - 500]' END";

        $rows = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->selectRaw("d.cartera, d.cosecha, DATE(g.fecha_gestion) AS dia, COUNT(*) AS total, {$rangeSql} AS rango")
            ->whereBetween('g.fecha_gestion', [
                $days[0].' 00:00:00',
                $days[4].' 23:59:59',
            ])
            ->groupBy('d.cartera', 'd.cosecha', DB::raw('DATE(g.fecha_gestion)'), DB::raw($rangeSql))
            ->get();

        $wallets = [
            'KP INVEST' => 'KPI',
            'TEC CENTER' => 'ALTAMIRA',
            'IMPULSE' => 'IMPULSE',
        ];
        $harvests = [];
        $ranges = [];
        foreach ($wallets as $wallet => $label) {
            $harvests[$wallet] = [];
            $ranges[$wallet] = [];
        }

        foreach ($rows as $row) {
            $wallet = trim((string) $row->cartera);
            if (! array_key_exists($wallet, $wallets)) {
                continue;
            }

            $harvest = $row->cosecha ?: 'SIN COSECHA';
            $harvests[$wallet][$harvest][$row->dia] =
                ($harvests[$wallet][$harvest][$row->dia] ?? 0) + (int) $row->total;
            $ranges[$wallet][$row->rango][$row->dia] =
                ($ranges[$wallet][$row->rango][$row->dia] ?? 0) + (int) $row->total;
        }

        $rangeOrder = [
            CapitalRange::from(50000),
            CapitalRange::from(20000),
            CapitalRange::from(10000),
            CapitalRange::from(5000),
            CapitalRange::from(1000),
            CapitalRange::from(501),
            CapitalRange::from(0),
        ];
        foreach ($ranges as $wallet => $values) {
            $ranges[$wallet] = collect($rangeOrder)
                ->mapWithKeys(fn (string $range) => [$range => $values[$range] ?? []])
                ->all();
        }

        return [
            'valor' => $week,
            'dias' => $days,
            'etiquetas' => $labels,
            'carteras' => collect($wallets)->map(fn (string $label, string $wallet) => [
                'nombre' => $wallet,
                'etiqueta' => $label,
                'cosechas' => $this->rowsWithTotals($harvests[$wallet], $days),
                'rangos' => $this->rowsWithTotals($ranges[$wallet], $days),
            ])->values(),
        ];
    }

    private function rowsWithTotals(array $rows, array $days): array
    {
        return collect($rows)->map(function (array $values, string $label) use ($days): array {
            $normalized = array_fill_keys($days, 0);
            foreach ($days as $day) {
                $normalized[$day] = (int) ($values[$day] ?? 0);
            }

            return [
                'etiqueta' => $label,
                'dias' => $normalized,
                'total' => array_sum($normalized),
            ];
        })->values()->all();
    }
}
