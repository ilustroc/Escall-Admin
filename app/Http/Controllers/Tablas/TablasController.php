<?php

namespace App\Http\Controllers\Tablas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Models\Pago; 

class TablasController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'gestiones'); // Default: gestiones

        $data = [
            'tab' => $tab,
        ];

        if ($tab === 'gestiones') {
            // Lógica existente de Gestiones (Mes + Semanal)
            $data = array_merge($data, $this->getDataGestiones($request));
        } elseif ($tab === 'pagos') {
            // Nueva lógica de Pagos (Ranking + Carteras)
            $data = array_merge($data, $this->getDataPagos($request));
        }

        return view('tablas.index', $data);
    }

// ==========================================
    // LÓGICA DE PAGOS (RANKING MATRIZ)
    // ==========================================
    private function getDataPagos(Request $request): array
    {
        // 1. Filtro de Mes
        $mes = $request->string('mes', Carbon::now()->format('Y-m'));
        [$year, $month] = explode('-', $mes);

        // 2. Definir las Carteras Fijas (Orden de columnas como la imagen)
        $carterasCols = ['KP INVEST', 'TEC CENTER', 'IMPULSE'];

        // 3. Obtener Datos Crudos
        $raw = Pago::whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->selectRaw('
                COALESCE(asesor, "SIN ASIGNAR") as asesor, 
                COALESCE(cartera, "OTRAS") as cartera, 
                SUM(monto) as monto, 
                COUNT(*) as ops
            ')
            ->groupBy('asesor', 'cartera')
            ->get();

        // 4. Procesar Matriz
        $matrix = [];
        $asesoresTotal = [];

        foreach ($raw as $r) {
            $asesor = $r->asesor;
            $cartera = strtoupper($r->cartera);

            // Inicializar asesor si no existe
            if (!isset($matrix[$asesor])) {
                $matrix[$asesor] = [
                    'carteras' => [],
                    'total_monto' => 0,
                    'total_ops' => 0
                ];
            }

            // Guardar datos en la celda correspondiente
            $matrix[$asesor]['carteras'][$cartera] = [
                'monto' => $r->monto,
                'ops'   => $r->ops
            ];

            // Acumular totales por fila (Asesor)
            $matrix[$asesor]['total_monto'] += $r->monto;
            $matrix[$asesor]['total_ops']   += $r->ops;
        }

        // 5. Ordenar por Monto Total Descendente (Ranking)
        uasort($matrix, fn($a, $b) => $b['total_monto'] <=> $a['total_monto']);

        // 6. Calcular Totales Generales (Pie de página)
        $footer = [
            'carteras' => [],
            'grand_monto' => 0,
            'grand_ops' => 0
        ];

        foreach ($carterasCols as $c) {
            $footer['carteras'][$c] = ['monto' => 0, 'ops' => 0];
        }

        foreach ($raw as $r) {
            $c = strtoupper($r->cartera);
            // Solo sumar al footer si la cartera está en nuestras columnas definidas
            // Opcional: Podrías agregar una columna "Otros" si quisieras
            if (in_array($c, $carterasCols)) {
                $footer['carteras'][$c]['monto'] += $r->monto;
                $footer['carteras'][$c]['ops'] += $r->ops;
            }
            $footer['grand_monto'] += $r->monto;
            $footer['grand_ops'] += $r->ops;
        }

        // KPI totales simples para el header
        $totalMes = $footer['grand_monto'];
        $countPagos = $footer['grand_ops'];
        // Nro. Pagos realizados en el mes
        $nroPagos = Pago::whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->count();

        return compact('mes', 'totalMes', 'countPagos', 'nroPagos', 'matrix', 'carterasCols', 'footer');
    }

    // ==========================================
    // LÓGICA DE GESTIONES (TU CÓDIGO ANTERIOR)
    // ==========================================
    private function getDataGestiones(Request $request): array
    {
        // --- 1. MENSUAL ---
        $mes = $request->string('mes')->toString();
        if (!preg_match('/^\d{4}\-\d{2}$/', $mes ?? '')) {
            $mes = Carbon::now()->format('Y-m');
        }
        [$y, $m] = explode('-', $mes);
        $start = Carbon::create((int)$y, (int)$m, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        $days = [];
        $c = $start->copy();
        while ($c->lte($end)) {
            $days[] = $c->toDateString();
            $c->addDay();
        }

        DB::connection()->disableQueryLog();
        $rows = DB::table('gestiones')
            ->selectRaw('COALESCE(NULLIF(TRIM(nombre), ""), "SIN NOMBRE") AS agente, DATE(fecha_gestion) AS dia, COUNT(*) AS total')
            ->whereBetween('fecha_gestion', [$start->toDateString(), $end->toDateString()])
            ->groupBy('agente', DB::raw('DATE(fecha_gestion)'))
            ->get();

        $table = [];
        foreach ($rows as $r) { $table[$r->agente][$r->dia] = (int)$r->total; }

        $totalDia = array_fill_keys($days, 0);
        foreach ($table as $byDay) {
            foreach ($days as $d) { $totalDia[$d] += $byDay[$d] ?? 0; }
        }

        // Filtro columnas vacías
        $visibleDays = [];
        foreach ($days as $d) {
            if (($totalDia[$d] ?? 0) > 0) $visibleDays[] = $d;
        }

        uasort($table, function ($a, $b) use ($visibleDays) {
            $sa=0; $sb=0;
            foreach ($visibleDays as $d) { $sa += $a[$d] ?? 0; $sb += $b[$d] ?? 0; }
            return $sb <=> $sa;
        });

        $totalGeneral = 0;
        foreach ($visibleDays as $d) { $totalGeneral += $totalDia[$d] ?? 0; }


        // --- 2. SEMANAL ---
        $week = $request->string('week')->toString();
        if (!preg_match('/^\d{4}-W\d{2}$/', $week ?? '')) {
            $week = Carbon::now()->format('o-\WW');
        }
        [$isoYear, $isoWeek] = sscanf($week, '%d-W%d');
        $wMonday = Carbon::now()->setISODate($isoYear, $isoWeek)->startOfDay();

        $wDays = []; $wLabels = [];
        for ($i=0; $i<5; $i++) {
            $d = $wMonday->copy()->addDays($i);
            $wDays[]   = $d->toDateString();
            $wLabels[] = ([
                'Monday'=>'lunes', 'Tuesday'=>'martes', 'Wednesday'=>'miércoles',
                'Thursday'=>'jueves', 'Friday'=>'viernes'
            ][$d->englishDayOfWeek] ?? $d->format('D')) . ' ' . $d->format('j');
        }
        $wFrom = $wDays[0] . ' 00:00:00';
        $wTo   = end($wDays) . ' 23:59:59';

        $caseRango = "CASE WHEN d.deuda_capital >= 50000 THEN '1.[50K - MAS]' WHEN d.deuda_capital >= 20000 THEN '2.[20K - 50K]' WHEN d.deuda_capital >= 10000 THEN '3.[10K - 20K]' WHEN d.deuda_capital >= 5000 THEN '4.[5K - 10K]' WHEN d.deuda_capital >= 1000 THEN '5.[1K - 5K]' WHEN d.deuda_capital >= 501 THEN '6.[501 - 1K]' ELSE '7.[0 - 500]' END";

        $wRows = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->selectRaw("d.cartera, d.cosecha, DATE(g.fecha_gestion) AS dia, COUNT(*) AS total, {$caseRango} AS rango")
            ->whereBetween('g.fecha_gestion', [$wFrom, $wTo])
            ->groupBy('d.cartera', 'd.cosecha', DB::raw('DATE(g.fecha_gestion)'), DB::raw($caseRango))
            ->get();

        $wCarteras = ['KP INVEST','TEC CENTER','IMPULSE'];
        $wCarteraLabel = ['KP INVEST' => 'KPI', 'TEC CENTER' => 'ALTAMIRA', 'IMPULSE' => 'IMPULSE'];
        $wPorCosecha = []; $wPorRango = [];
        foreach ($wCarteras as $c) { $wPorCosecha[$c]=[]; $wPorRango[$c]=[]; }

        foreach ($wRows as $r) {
            $c = trim((string)$r->cartera);
            if (!isset($wPorCosecha[$c])) continue;
            $co = $r->cosecha ?: 'SIN COSECHA';
            $d  = $r->dia;
            $n  = (int)$r->total;
            $rg = $r->rango;
            $wPorCosecha[$c][$co][$d] = ($wPorCosecha[$c][$co][$d] ?? 0) + $n;
            $wPorRango[$c][$rg][$d]   = ($wPorRango[$c][$rg][$d]   ?? 0) + $n;
        }

        // Orden de rangos fijo
        $rangosOrden = ['1.[50K - MAS]','2.[20K - 50K]','3.[10K - 20K]','4.[5K - 10K]','5.[1K - 5K]','6.[501 - 1K]','7.[0 - 500]'];
        foreach ($wPorRango as $c=>&$mat){
            $ord=[]; foreach($rangosOrden as $r) $ord[$r] = $mat[$r] ?? [];
            $mat = $ord;
        } unset($mat);

        return compact('mes', 'days', 'table', 'totalDia', 'totalGeneral', 'visibleDays', 'week', 'wDays', 'wLabels', 'wCarteras', 'wCarteraLabel', 'wPorCosecha', 'wPorRango');
    }
}