<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Expertis\ExpertisDashboardController;
use App\Models\Gestion;
use App\Models\Pago;
use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(
        ExpertisDashboardController $expertisDashboard,
        ExpertisReportQueryService $reportes,
    ): Response {
        $hoy = now()->format('Y-m-d');

        return Inertia::render('Expertis/Dashboard', [
            'expertis' => $expertisDashboard->data($reportes),
            'legacy' => [
                'gestiones_hoy' => Schema::hasTable('gestiones')
                    ? Gestion::whereDate('created_at', $hoy)->count()
                    : 0,
                'pagos_hoy' => Schema::hasTable('pagos')
                    ? (float) Pago::whereDate('fecha', $hoy)->sum('monto')
                    : 0,
                'ultima_carga' => Schema::hasTable('gestiones')
                    ? Gestion::latest('created_at')->value('created_at')
                    : null,
            ],
        ]);
    }
}
