<?php

namespace App\Queries\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpertisDashboardQuery
{
    public function __construct(
        private readonly ExpertisReportQuery $reports,
    ) {}

    public function get(): array
    {
        if (! $this->tablesAvailable()) {
            return [
                'disponible' => false,
                'metricas' => [],
                'gestiones_por_dia' => [],
                'pagos_por_dia' => [],
                'top_asesores' => [],
                'top_tipificaciones' => [],
                'actividad' => [],
            ];
        }

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $chartStart = now()->subDays(29)->toDateString();
        $lastImport = ImportacionExpertis::query()->latest()->first();
        $monthUnique = $this->reports->gestiones([
            'fecha_inicio' => $monthStart,
            'fecha_fin' => $today,
            'unico' => '1',
        ], false);

        return [
            'disponible' => true,
            'metricas' => [
                'gestiones_hoy' => DB::table('gestiones_expertis')
                    ->whereDate('fecha_llamada', $today)
                    ->count(),
                'gestiones_mes' => DB::table('gestiones_expertis')
                    ->whereBetween('fecha_llamada', [$monthStart, $today])
                    ->count(),
                'pagos_hoy' => DB::table('pagos_expertis')
                    ->whereDate('fecha', $today)
                    ->count(),
                'recaudo_mes' => (float) DB::table('pagos_expertis')
                    ->whereBetween('fecha', [$monthStart, $today])
                    ->sum('monto'),
                'ultima_gestion' => ImportacionExpertis::query()
                    ->where('tipo', 'gestiones')
                    ->latest()
                    ->first(),
                'ultimo_pago' => ImportacionExpertis::query()
                    ->where('tipo', 'pagos')
                    ->latest()
                    ->first(),
                'duplicados_ultima' => $lastImport?->filas_duplicadas ?? 0,
                'archivos_con_errores' => ImportacionExpertis::query()
                    ->where('filas_error', '>', 0)
                    ->count(),
            ],
            'gestiones_por_dia' => DB::table('gestiones_expertis')
                ->whereBetween('fecha_llamada', [$chartStart, $today])
                ->select('fecha_llamada as fecha')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('fecha_llamada')
                ->orderBy('fecha_llamada')
                ->get(),
            'pagos_por_dia' => DB::table('pagos_expertis')
                ->whereBetween('fecha', [$chartStart, $today])
                ->select('fecha')
                ->selectRaw('SUM(monto) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get(),
            'top_asesores' => DB::query()->fromSub(clone $monthUnique, 'g')
                ->selectRaw("COALESCE(asesor, 'SIN ASESOR') as etiqueta")
                ->selectRaw('COUNT(*) as total')
                ->groupBy('asesor')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'top_tipificaciones' => DB::query()->fromSub(clone $monthUnique, 'g')
                ->selectRaw("COALESCE(nivel_2, 'SIN TIPIFICACIÓN') as etiqueta")
                ->selectRaw('COUNT(*) as total')
                ->groupBy('nivel_2')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'actividad' => ImportacionExpertis::query()
                ->with('usuario:id,name')
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('importaciones_expertis')
            && Schema::hasTable('gestiones_expertis')
            && Schema::hasTable('pagos_expertis')
            && Schema::hasTable('tipificaciones_expertis');
    }
}
