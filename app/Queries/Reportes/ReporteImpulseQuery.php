<?php

namespace App\Queries\Reportes;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReporteImpulseQuery
{
    public function count(string $from, string $to, int $team = 2): int
    {
        return $this->builder($from, $to, $team)->count();
    }

    public function builder(string $from, string $to, int $team = 2): Builder
    {
        $start = $from.' 00:00:00';
        $endExclusive = Carbon::parse($to)->addDay()->startOfDay()->toDateTimeString();
        $managementType = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $catalogType = "REPLACE(REPLACE(UPPER(TRIM(ti.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        $query = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->leftJoin('tipificaciones_impulsego as ti', DB::raw($managementType), '=', DB::raw($catalogType))
            ->where('d.cartera', 'like', '%IMPULSE%')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<', $endExclusive)
            ->selectRaw("
                g.dni AS documento,
                d.titular AS cliente,
                COALESCE(ti.accion, g.tipificacion) AS accion,
                COALESCE(ti.contacto, g.status) AS contacto,
                COALESCE(NULLIF(TRIM(g.nombre), ''), 'SIN NOMBRE') AS agente,
                d.codigo AS operacion,
                d.entidad AS entidad,
                g.fecha_gestion AS fecha_gestion,
                g.telefono AS telefono,
                g.observacion AS observacion,
                g.monto_pago AS monto_promesa,
                CASE WHEN g.monto_pago IS NULL OR g.monto_pago = 0 THEN 0 ELSE 1 END AS nro_cuotas,
                g.fecha_pago AS fecha_promesa
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni');

        if ($team === 3) {
            $query->where('d.entidad', 'BCP');
        } else {
            $query->where(function (Builder $builder): void {
                $builder->whereNull('d.entidad')->orWhere('d.entidad', '<>', 'BCP');
            });
        }

        return $query;
    }
}
