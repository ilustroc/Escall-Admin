<?php

namespace App\Queries\Reportes;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReporteKpInvestQuery
{
    public function count(string $from, string $to): int
    {
        return $this->builder($from, $to)->count();
    }

    public function builder(string $from, string $to): Builder
    {
        $start = $from.' 00:00:00';
        $endExclusive = Carbon::parse($to)->addDay()->startOfDay()->toDateTimeString();
        $managementType = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $catalogType = "REPLACE(REPLACE(UPPER(TRIM(tk.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->leftJoin('tipificaciones_kpinvest as tk', DB::raw($managementType), '=', DB::raw($catalogType))
            ->where('d.cartera', 'KP INVEST')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<', $endExclusive)
            ->selectRaw("
                g.fecha_gestion AS fecha_gest,
                CASE
                    WHEN CHAR_LENGTH(g.dni) = 8 THEN 'DNI'
                    WHEN CHAR_LENGTH(g.dni) = 11 THEN 'RUC'
                    ELSE 'DNI'
                END AS tip_doc,
                COALESCE(CAST(g.dni AS CHAR), '') AS num_doc,
                d.titular AS cliente,
                COALESCE(CAST(g.telefono AS CHAR), '') AS telefono,
                'LLAMADA ENTRANTE' AS accion,
                COALESCE(tk.tipificacion_kpinvest, g.tipificacion, '') AS tipificacion,
                COALESCE(tk.estado, g.status, '') AS estado,
                g.observacion AS gestion,
                g.fecha_pago AS fecha_promesa,
                g.monto_pago AS monto_promesa
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni');
    }
}
