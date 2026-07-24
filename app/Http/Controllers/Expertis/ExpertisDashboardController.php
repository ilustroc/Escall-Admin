<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Models\ImportacionExpertis;
use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ExpertisDashboardController extends Controller
{
    public function index(ExpertisReportQueryService $reportes): Response
    {
        return Inertia::render('Expertis/Dashboard', [
            'expertis' => $this->data($reportes),
            'legacy' => null,
        ]);
    }

    public function data(ExpertisReportQueryService $reportes): array
    {
        if (! $this->tablasDisponibles()) {
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

        $hoy = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();
        $inicioGrafico = now()->subDays(29)->toDateString();
        $ultima = ImportacionExpertis::query()->latest()->first();

        $baseUnicosMes = $reportes->gestiones([
            'fecha_inicio' => $inicioMes,
            'fecha_fin' => $hoy,
            'unico' => '1',
        ], false);

        return [
            'disponible' => true,
            'metricas' => [
                'gestiones_hoy' => DB::table('gestiones_expertis')->whereDate('fecha_llamada', $hoy)->count(),
                'gestiones_mes' => DB::table('gestiones_expertis')->whereBetween('fecha_llamada', [$inicioMes, $hoy])->count(),
                'pagos_hoy' => DB::table('pagos_expertis')->whereDate('fecha', $hoy)->count(),
                'recaudo_mes' => (float) DB::table('pagos_expertis')->whereBetween('fecha', [$inicioMes, $hoy])->sum('monto'),
                'ultima_gestion' => ImportacionExpertis::query()->where('tipo', 'gestiones')->latest()->first(),
                'ultimo_pago' => ImportacionExpertis::query()->where('tipo', 'pagos')->latest()->first(),
                'duplicados_ultima' => $ultima?->filas_duplicadas ?? 0,
                'archivos_con_errores' => ImportacionExpertis::query()->where('filas_error', '>', 0)->count(),
            ],
            'gestiones_por_dia' => DB::table('gestiones_expertis')
                ->whereBetween('fecha_llamada', [$inicioGrafico, $hoy])
                ->select('fecha_llamada as fecha')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('fecha_llamada')
                ->orderBy('fecha_llamada')
                ->get(),
            'pagos_por_dia' => DB::table('pagos_expertis')
                ->whereBetween('fecha', [$inicioGrafico, $hoy])
                ->select('fecha')
                ->selectRaw('SUM(monto) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get(),
            'top_asesores' => DB::query()->fromSub(clone $baseUnicosMes, 'g')
                ->selectRaw("COALESCE(asesor, 'SIN ASESOR') as etiqueta")
                ->selectRaw('COUNT(*) as total')
                ->groupBy('asesor')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'top_tipificaciones' => DB::query()->fromSub(clone $baseUnicosMes, 'g')
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

    private function tablasDisponibles(): bool
    {
        return Schema::hasTable('importaciones_expertis')
            && Schema::hasTable('gestiones_expertis')
            && Schema::hasTable('pagos_expertis')
            && Schema::hasTable('tipificaciones_expertis');
    }
}
