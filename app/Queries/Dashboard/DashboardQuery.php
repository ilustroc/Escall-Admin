<?php

namespace App\Queries\Dashboard;

use App\Models\Gestion;
use App\Models\Pago;
use App\Queries\Expertis\ExpertisDashboardQuery;
use Illuminate\Support\Facades\Schema;

class DashboardQuery
{
    public function __construct(
        private readonly ExpertisDashboardQuery $expertis,
    ) {}

    public function get(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'legacy' => [
                'gestiones_hoy' => $this->has('gestiones')
                    ? Gestion::query()->whereDate('fecha_gestion', $today)->count()
                    : 0,
                'gestiones_mes' => $this->has('gestiones')
                    ? Gestion::query()->whereBetween('fecha_gestion', [$monthStart, $today])->count()
                    : 0,
                'pagos_hoy' => $this->has('pagos')
                    ? (float) Pago::query()->whereDate('fecha', $today)->sum('monto')
                    : 0,
                'pagos_mes' => $this->has('pagos')
                    ? (float) Pago::query()->whereBetween('fecha', [$monthStart, $today])->sum('monto')
                    : 0,
                'ultima_gestion' => $this->has('gestiones')
                    ? Gestion::query()->latest('created_at')->value('created_at')
                    : null,
                'ultimo_pago' => $this->has('pagos')
                    ? Pago::query()->latest('created_at')->value('created_at')
                    : null,
            ],
            'expertis' => $this->expertis->get(),
        ];
    }

    private function has(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
