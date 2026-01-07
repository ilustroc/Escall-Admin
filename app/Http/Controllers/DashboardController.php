<?php

namespace App\Http\Controllers;

use App\Services\ScheduleStatusService;
use App\Models\Gestion;
use App\Models\Pago;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Calcular totales del día (Ejemplos rápidos)
        // Ajusta los modelos según tus nombres reales
        $hoy = now()->format('Y-m-d');
        
        // Gestiones de hoy
        $gestionesHoy = Gestion::whereDate('created_at', $hoy)->count();
        
        // Pagos de hoy (Suma)
        $pagosHoy = Pago::whereDate('fecha', $hoy)->sum('monto');
        
        // Última carga (fecha)
        $ultimaCarga = Gestion::latest('created_at')->value('created_at');

        // Tareas simuladas (manteniendo tu lógica actual)
        $tasks = [
            [
                'label' => 'Actualización de gestiones',
                'urgency' => 'soon',
                'in' => '21m 42s',
                'next_at_label' => date('d/m/Y 11:00'),
                'last_at_label' => date('d/m/Y 10:00'),
            ],
            [
                'label' => 'Reporte Impulse',
                'urgency' => 'muted',
                'in' => '8h 21m',
                'next_at_label' => date('d/m/Y 19:00'),
                'last_at_label' => date('d/m/Y 10:00'),
            ]
        ];

        return view('dashboard', compact('gestionesHoy', 'pagosHoy', 'ultimaCarga', 'tasks'));
    }
}
