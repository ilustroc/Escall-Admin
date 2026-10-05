<?php

namespace App\Queries\Expertis;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ExpertisReportQuery
{
    public function gestiones(array $filtros = [], bool $ordenar = true): Builder
    {
        $ranking = DB::query()
            ->fromSub($this->fuentesGestiones(), 'ge')
            // A discador result may not exist in this catalog. LEFT JOIN keeps
            // it in the report with peso/categoria null instead of failing.
            ->leftJoin('tipificaciones_expertis as te', 'te.tipificacion', '=', 'ge.nivel_2')
            ->select('ge.*', 'te.peso', 'te.gestion as categoria_gestion')
            ->selectRaw(
                'ROW_NUMBER() OVER (
                    PARTITION BY ge.codigo_normalizado
                    ORDER BY COALESCE(te.peso, 999999) ASC, ge.fecha_hora DESC, ge.registro_key DESC
                ) as ranking_unico',
            );

        $pagos = DB::query()
            ->fromSub($this->fuentesGestiones(), 'gp')
            ->leftJoin('pagos_expertis as pp', function ($join): void {
                $join->on('pp.cuenta_normalizada', '=', 'gp.codigo_normalizado')
                    ->whereColumn('pp.fecha', '>=', 'gp.fecha_llamada');
            })
            ->select('gp.registro_key')
            ->selectRaw('MIN(pp.fecha) as fecha_pagada')
            ->selectRaw('COALESCE(SUM(pp.monto), 0) as monto_pagado')
            ->groupBy('gp.registro_key');

        $query = DB::query()
            ->fromSub($ranking, 'r')
            ->leftJoinSub($pagos, 'pag', 'pag.registro_key', '=', 'r.registro_key')
            ->select('r.*', 'pag.fecha_pagada')
            ->selectRaw('COALESCE(pag.monto_pagado, 0) as monto_pagado')
            ->selectRaw("CASE WHEN COALESCE(pag.monto_pagado, 0) > 0 THEN 'PAGO' ELSE 'NO PAGO' END as estado")
            ->selectRaw('CASE WHEN r.ranking_unico = 1 THEN 1 ELSE 0 END as unico')
            ->selectRaw($this->proyectadoSql('r').' as ps_proyectado');

        $this->aplicarFiltrosGestiones($query, $filtros);

        if ($ordenar) {
            $ordenes = [
                'codigo' => 'r.codigo',
                'dni' => 'r.dni',
                'cartera' => 'r.cartera',
                'asesor' => 'r.asesor',
                'equipo' => 'r.equipo',
                'fecha_llamada' => 'r.fecha_llamada',
                'fecha_hora' => 'r.fecha_hora',
                'nivel_1' => 'r.nivel_1',
                'nivel_2' => 'r.nivel_2',
                'peso' => 'r.peso',
                'monto' => 'r.monto',
                'monto_pagado' => 'monto_pagado',
                'unico' => 'r.ranking_unico',
            ];
            $orden = $ordenes[$filtros['sort'] ?? 'fecha_hora'] ?? 'r.fecha_hora';
            $direccion = ($filtros['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orden, $direccion)->orderByDesc('r.id');
        }

        return $query;
    }

    public function pagos(array $filtros = [], bool $ordenar = true): Builder
    {
        $query = DB::table('pagos_expertis as pe')
            ->leftJoin('importaciones_expertis as ie', 'ie.id', '=', 'pe.importacion_expertis_id')
            ->select('pe.*', 'ie.nombre_original as archivo_origen')
            ->selectRaw(
                'CASE WHEN EXISTS (
                    SELECT 1
                    FROM gestiones_expertis gx
                    WHERE gx.codigo_normalizado = pe.cuenta_normalizada
                      AND gx.fecha_llamada <= pe.fecha
                ) THEN 1 ELSE 0 END as tiene_gestion_previa',
            )
            ->selectRaw(
                '(SELECT gx2.cartera
                    FROM gestiones_expertis gx2
                    WHERE gx2.codigo_normalizado = pe.cuenta_normalizada
                      AND gx2.fecha_llamada <= pe.fecha
                    ORDER BY gx2.fecha_llamada DESC, gx2.id DESC
                    LIMIT 1) as cartera_relacionada',
            );

        $this->aplicarFiltrosPagos($query, $filtros);

        if ($ordenar) {
            $ordenes = [
                'fecha' => 'pe.fecha',
                'cuenta' => 'pe.cuenta',
                'dni' => 'pe.dni',
                'monto' => 'pe.monto',
                'ejecutivo' => 'pe.ejecutivo',
                'tipo_acuerdo' => 'pe.tipo_acuerdo',
                'recaudo' => 'pe.recaudo',
                'created_at' => 'pe.created_at',
            ];
            $orden = $ordenes[$filtros['sort'] ?? 'fecha'] ?? 'pe.fecha';
            $direccion = ($filtros['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orden, $direccion)->orderByDesc('pe.id');
        }

        return $query;
    }

    public function resumenGestiones(array $filtros = []): array
    {
        if (! array_key_exists('unico', $filtros) || $filtros['unico'] === '') {
            $filtros['unico'] = '1';
        }

        $base = $this->gestiones($filtros, false);
        $totales = DB::query()->fromSub(clone $base, 'g')
            ->selectRaw('COUNT(*) as total_gestiones')
            ->selectRaw('COUNT(DISTINCT codigo_normalizado) as clientes_unicos')
            ->selectRaw("SUM(CASE WHEN categoria_gestion = 'CEF' THEN 1 ELSE 0 END) as contactos_efectivos")
            ->selectRaw("SUM(CASE WHEN categoria_gestion IN ('CNE', 'NOC') OR categoria_gestion IS NULL THEN 1 ELSE 0 END) as no_contactos")
            ->selectRaw("SUM(CASE WHEN nivel_2 IN ('PPC', 'PPM', 'RPP') THEN 1 ELSE 0 END) as promesas")
            ->selectRaw('COUNT(DISTINCT CASE WHEN monto_pagado > 0 THEN codigo_normalizado END) as clientes_con_pago')
            ->selectRaw('COALESCE(SUM(ps_proyectado), 0) as monto_proyectado')
            ->selectRaw('COALESCE(SUM(monto_pagado), 0) as monto_pagado')
            ->first();

        $clientes = (int) ($totales->clientes_unicos ?? 0);
        $clientesConPago = (int) ($totales->clientes_con_pago ?? 0);

        return [
            'totales' => [
                'total_gestiones' => (int) ($totales->total_gestiones ?? 0),
                'clientes_unicos' => $clientes,
                'contactos_efectivos' => (int) ($totales->contactos_efectivos ?? 0),
                'no_contactos' => (int) ($totales->no_contactos ?? 0),
                'promesas' => (int) ($totales->promesas ?? 0),
                'clientes_con_pago' => $clientesConPago,
                'monto_proyectado' => (float) ($totales->monto_proyectado ?? 0),
                'monto_pagado' => (float) ($totales->monto_pagado ?? 0),
                'conversion' => $clientes > 0
                    ? round(($clientesConPago / $clientes) * 100, 2)
                    : 0,
            ],
            'por_asesor' => $this->agruparGestiones($base, 'asesor', 15),
            'por_cartera' => $this->agruparGestiones($base, 'cartera', 15),
            'por_tipificacion' => $this->agruparGestiones($base, 'nivel_2', 20),
            'evolucion_diaria' => DB::query()->fromSub(clone $base, 'g')
                ->select('fecha_llamada as fecha')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COALESCE(SUM(monto_pagado), 0) as monto_pagado')
                ->groupBy('fecha_llamada')
                ->orderBy('fecha_llamada')
                ->limit(366)
                ->get(),
        ];
    }

    public function resumenPagos(array $filtros = []): array
    {
        $base = $this->pagos($filtros, false);
        $totales = DB::query()->fromSub(clone $base, 'p')
            ->selectRaw('COALESCE(SUM(monto), 0) as monto_total')
            ->selectRaw('COUNT(*) as cantidad_pagos')
            ->selectRaw('COUNT(DISTINCT cuenta_normalizada) as clientes_unicos')
            ->selectRaw('COALESCE(AVG(monto), 0) as pago_promedio')
            ->selectRaw('SUM(CASE WHEN tiene_gestion_previa = 1 THEN 1 ELSE 0 END) as con_gestion_previa')
            ->selectRaw('SUM(CASE WHEN tiene_gestion_previa = 0 THEN 1 ELSE 0 END) as sin_gestion_previa')
            ->first();

        return [
            'totales' => [
                'monto_total' => (float) ($totales->monto_total ?? 0),
                'cantidad_pagos' => (int) ($totales->cantidad_pagos ?? 0),
                'clientes_unicos' => (int) ($totales->clientes_unicos ?? 0),
                'pago_promedio' => (float) ($totales->pago_promedio ?? 0),
                'con_gestion_previa' => (int) ($totales->con_gestion_previa ?? 0),
                'sin_gestion_previa' => (int) ($totales->sin_gestion_previa ?? 0),
            ],
            'por_ejecutivo' => DB::query()->fromSub(clone $base, 'p')
                ->selectRaw("COALESCE(ejecutivo, 'SIN EJECUTIVO') as etiqueta")
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COALESCE(SUM(monto), 0) as monto')
                ->groupBy('ejecutivo')
                ->orderByDesc('monto')
                ->limit(15)
                ->get(),
            'por_cartera' => DB::query()->fromSub(clone $base, 'p')
                ->selectRaw("COALESCE(cartera_relacionada, 'SIN GESTIÓN PREVIA') as etiqueta")
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COALESCE(SUM(monto), 0) as monto')
                ->groupBy('cartera_relacionada')
                ->orderByDesc('monto')
                ->limit(15)
                ->get(),
            'por_dia' => DB::query()->fromSub(clone $base, 'p')
                ->select('fecha')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COALESCE(SUM(monto), 0) as monto')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->limit(366)
                ->get(),
        ];
    }

    public function opcionesFiltros(): array
    {
        $distintosGestiones = function (string $columna): array {
            return DB::query()
                ->fromSub($this->fuentesGestiones(), 'g')
                ->whereNotNull($columna)
                ->where($columna, '<>', '')
                ->distinct()
                ->orderBy($columna)
                ->limit(250)
                ->pluck($columna)
                ->all();
        };

        $distintos = function (string $tabla, string $columna): array {
            return DB::table($tabla)
                ->whereNotNull($columna)
                ->where($columna, '<>', '')
                ->distinct()
                ->orderBy($columna)
                ->limit(250)
                ->pluck($columna)
                ->all();
        };

        return [
            'carteras' => $distintosGestiones('cartera'),
            'asesores' => $distintosGestiones('asesor'),
            'equipos' => $distintosGestiones('equipo'),
            'niveles_1' => $distintosGestiones('nivel_1'),
            'niveles_2' => $distintosGestiones('nivel_2'),
            'campanias' => $distintosGestiones('campania'),
            'medios' => $distintosGestiones('medio_gestion'),
            'ejecutivos' => $distintos('pagos_expertis', 'ejecutivo'),
            'tipos_acuerdo' => $distintos('pagos_expertis', 'tipo_acuerdo'),
            'recaudos' => $distintos('pagos_expertis', 'recaudo'),
        ];
    }

    private function aplicarFiltrosGestiones(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['codigo'] ?? null, fn ($q, $v) => $q->where('r.codigo', 'like', '%'.$v.'%'))
            ->when($filtros['dni'] ?? null, fn ($q, $v) => $q->where('r.dni', 'like', '%'.$v.'%'))
            ->when($filtros['cartera'] ?? null, fn ($q, $v) => $q->where('r.cartera', $v))
            ->when($filtros['asesor'] ?? null, fn ($q, $v) => $q->where('r.asesor', $v))
            ->when($filtros['equipo'] ?? null, fn ($q, $v) => $q->where('r.equipo', $v))
            ->when($filtros['nivel_1'] ?? null, fn ($q, $v) => $q->where('r.nivel_1', $v))
            ->when($filtros['nivel_2'] ?? null, fn ($q, $v) => $q->where('r.nivel_2', $v))
            ->when($filtros['peso'] ?? null, fn ($q, $v) => $q->where('r.peso', $v))
            ->when($filtros['campania'] ?? null, fn ($q, $v) => $q->where('r.campania', $v))
            ->when($filtros['medio_gestion'] ?? null, fn ($q, $v) => $q->where('r.medio_gestion', $v));

        if ($from = $filtros['fecha_inicio'] ?? null) {
            $query->where('r.fecha_hora', '>=', CarbonImmutable::parse($from, 'America/Lima')->startOfDay()->format('Y-m-d H:i:s'));
        }

        if ($to = $filtros['fecha_fin'] ?? null) {
            $query->where('r.fecha_hora', '<', CarbonImmutable::parse($to, 'America/Lima')->startOfDay()->addDay()->format('Y-m-d H:i:s'));
        }

        if (($filtros['estado'] ?? '') === 'PAGO') {
            $query->whereRaw('COALESCE(pag.monto_pagado, 0) > 0');
        } elseif (($filtros['estado'] ?? '') === 'NO PAGO') {
            $query->whereRaw('COALESCE(pag.monto_pagado, 0) = 0');
        }

        if (($filtros['unico'] ?? '') !== '') {
            if ((int) $filtros['unico'] === 1) {
                $query->where('r.ranking_unico', 1);
            } else {
                $query->where('r.ranking_unico', '>', 1);
            }
        }
    }

    private function aplicarFiltrosPagos(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['fecha_inicio'] ?? null, fn ($q, $v) => $q->where('pe.fecha', '>=', $v))
            ->when($filtros['fecha_fin'] ?? null, fn ($q, $v) => $q->where('pe.fecha', '<=', $v))
            ->when($filtros['cuenta'] ?? null, fn ($q, $v) => $q->where('pe.cuenta', 'like', '%'.$v.'%'))
            ->when($filtros['dni'] ?? null, fn ($q, $v) => $q->where('pe.dni', 'like', '%'.$v.'%'))
            ->when($filtros['ejecutivo'] ?? null, fn ($q, $v) => $q->where('pe.ejecutivo', $v))
            ->when($filtros['tipo_acuerdo'] ?? null, fn ($q, $v) => $q->where('pe.tipo_acuerdo', $v))
            ->when($filtros['recaudo'] ?? null, fn ($q, $v) => $q->where('pe.recaudo', $v))
            ->when($filtros['monto_min'] ?? null, fn ($q, $v) => $q->where('pe.monto', '>=', $v))
            ->when($filtros['monto_max'] ?? null, fn ($q, $v) => $q->where('pe.monto', '<=', $v));
    }

    private function agruparGestiones(Builder $base, string $campo, int $limite)
    {
        $permitidos = ['asesor', 'cartera', 'nivel_2'];
        if (! in_array($campo, $permitidos, true)) {
            throw new \InvalidArgumentException('Campo de agrupación no permitido.');
        }

        return DB::query()->fromSub(clone $base, 'g')
            ->selectRaw("COALESCE({$campo}, 'SIN DATOS') as etiqueta")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(DISTINCT codigo_normalizado) as clientes')
            ->selectRaw('COALESCE(SUM(monto_pagado), 0) as monto_pagado')
            ->groupBy($campo)
            ->orderByDesc('total')
            ->limit($limite)
            ->get();
    }

    private function proyectadoSql(string $alias): string
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return "CASE WHEN {$alias}.monto > 0 THEN {$alias}.monto ELSE 0 END";
        }

        return "CASE
            WHEN {$alias}.monto > 0 THEN {$alias}.monto
            WHEN {$alias}.nivel_2 = 'VLL'
              AND LOCATE('MONTO TOTAL NEGOCIADO', UPPER(COALESCE({$alias}.observacion, ''))) > 0
            THEN CAST(
                REPLACE(
                    REGEXP_REPLACE(
                        SUBSTRING_INDEX(
                            SUBSTRING(
                                UPPER({$alias}.observacion),
                                LOCATE('MONTO TOTAL NEGOCIADO', UPPER({$alias}.observacion)) + 22
                            ),
                            '/',
                            1
                        ),
                        '[^0-9,.-]',
                        ''
                    ),
                    ',',
                    '.'
                ) AS DECIMAL(14,2)
            )
            ELSE 0
        END";
    }

    private function fuentesGestiones(): Builder
    {
        $assignments = $this->ultimaAsignacionPorDocumento();
        $driver = DB::connection()->getDriverName();
        $manualKey = $driver === 'mysql' ? "CONCAT('MANUAL:', ge.id)" : "'MANUAL:' || ge.id";
        $dialerKey = $driver === 'mysql' ? "CONCAT('DISCADOR:', ll.id)" : "'DISCADOR:' || ll.id";
        $periodoManual = $driver === 'mysql' ? "DATE_FORMAT(ge.fecha_hora, '%Y%m')" : "STRFTIME('%Y%m', ge.fecha_hora)";
        $periodoDiscador = $driver === 'mysql' ? "DATE_FORMAT(ll.fecha_gestion, '%Y%m')" : "STRFTIME('%Y%m', ll.fecha_gestion)";

        $manual = DB::table('gestiones_expertis as ge')
            ->leftJoinSub($assignments, 'ae', function ($join) use ($periodoManual): void {
                $join->on('ae.documento', '=', DB::raw('TRIM(ge.dni)'))
                    ->on('ae.periodo', '=', DB::raw($periodoManual));
            })
            ->selectRaw("ge.id as id, ge.id as id_gestion, 'MANUAL' as origen, {$manualKey} as registro_key")
            ->selectRaw("COALESCE(NULLIF(ge.codigo, ''), ae.codigo, TRIM(ge.dni)) as codigo")
            ->selectRaw("COALESCE(NULLIF(ge.codigo_normalizado, ''), ae.codigo_normalizado, TRIM(ge.dni)) as codigo_normalizado")
            ->selectRaw("COALESCE(NULLIF(ge.canal_gestion, ''), 'ESCALL') as canal_gestion")
            ->selectRaw("COALESCE(NULLIF(ge.canal_asignacion, ''), 'ESCALL') as canal_asignacion")
            ->selectRaw('TRIM(ge.dni) as dni')
            ->selectRaw("COALESCE(NULLIF(ge.nombre_cliente, ''), ae.titular) as nombre_cliente")
            ->addSelect([
                'ge.cartera', 'ge.asesor', 'ge.equipo', 'ge.telefono',
                'ge.fecha_llamada', 'ge.hora', 'ge.fecha_hora', 'ge.campania',
                'ge.nivel_1', 'ge.nivel_2', 'ge.fecha_compromiso', 'ge.monto', 'ge.observacion',
            ])
            ->selectRaw("'MANUAL' as medio_gestion");

        $llamadas = DB::table('llamadas as ll')
            ->leftJoinSub($assignments, 'ae', function ($join) use ($periodoDiscador): void {
                $join->on('ae.documento', '=', DB::raw('TRIM(ll.documento)'))
                    ->on('ae.periodo', '=', DB::raw($periodoDiscador));
            })
            ->selectRaw("ll.id as id, ll.id as id_gestion, 'DISCADOR' as origen, {$dialerKey} as registro_key")
            ->selectRaw("COALESCE(NULLIF(ae.codigo, ''), TRIM(ll.documento)) as codigo")
            ->selectRaw("COALESCE(NULLIF(ae.codigo_normalizado, ''), TRIM(ll.documento)) as codigo_normalizado")
            ->selectRaw("'ESCALL' as canal_gestion, 'ESCALL' as canal_asignacion")
            ->selectRaw('TRIM(ll.documento) as dni')
            ->selectRaw("COALESCE(NULLIF(ll.cliente, ''), ae.titular) as nombre_cliente")
            ->addSelect([
                'll.cartera',
            ])
            ->selectRaw("UPPER(COALESCE(NULLIF(TRIM(ll.usuario), ''), 'MARCADOR')) as asesor")
            ->selectRaw("'KATHERINE HUAMAN' as equipo")
            ->selectRaw('ll.telefono as telefono')
            ->selectRaw('DATE(ll.fecha_gestion) as fecha_llamada')
            ->selectRaw('TIME(ll.fecha_gestion) as hora')
            ->selectRaw('ll.fecha_gestion as fecha_hora')
            ->selectRaw('ll.campana as campania')
            ->selectRaw('ll.resultado as nivel_1')
            ->selectRaw('ll.tipo_gestion as nivel_2')
            ->selectRaw('NULL as fecha_compromiso, 0 as monto')
            ->selectRaw('ll.observacion as observacion')
            ->selectRaw("'DISCADOR' as medio_gestion");

        return $manual->unionAll($llamadas);
    }

    private function ultimaAsignacionPorDocumento(): Builder
    {
        return DB::table('asignaciones_expertis as ae')
            ->select('ae.documento', 'ae.periodo', 'ae.codigo', 'ae.codigo_normalizado', 'ae.titular')
            ->whereRaw('ae.id = (
                SELECT ae2.id
                FROM asignaciones_expertis as ae2
                WHERE ae2.documento = ae.documento
                  AND ae2.periodo = ae.periodo
                ORDER BY ae2.id DESC
                LIMIT 1
            )');
    }
}
