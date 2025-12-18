<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\AsignacionTecCenterPlaceholderExport;
use App\Exports\ReporteDataCarterasExport;
use App\Exports\ReporteDataTecCenterMesExport;
use App\Exports\ReporteTecCenterMesExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

class ReporteCarterasController extends Controller
{
    private const HEADERS_REPORTE_DATA = [
        'CARTERA','DNI','TITULAR','CODIGO','ENTIDAD','COSECHA','PRODUCTO','SUB_PRODUCTO',
        'HISTORICO','DEPARTAMENTO','RANGO','DEUDA TOTAL','DEUDA CAPITAL','CAMPAÑA','%',
        'MC','SITUACION','TELEFONOS',
        'TELEFONO - ULTIMA GESTION','STATUS - ULTIMA GESTION','TIPIFICACION - ULTIMA GESTION',
        'OBSERVACION - ULTIMA GESTION','FECHA - ULTIMA GESTION',
        'TELEFONO - MEJOR GESTION','STATUS - MEJOR GESTION','TIPIFICACION - MEJOR GESTION',
        'OBSERVACION - MEJOR GESTION','FECHA - MEJOR GESTION',
        'INTENSIDAD',
    ];

    public function index(Request $r)
    {
        $tag  = $r->query('tag',  'OCTUBRE25');
        $lote = $r->query('lote', '1809');
        $mes  = $r->query('mes', Carbon::now()->format('Y-m'));
        return view('reportes.carteras', compact('tag', 'lote', 'mes'));
    }

    public function exportData(Request $r)
    {
        $tag = $r->query('tag', 'OCTUBRE25');
        $mes = $this->assertMonth($r->query('mes'));

        return Excel::download(
            (new ReporteDataCarterasExport)->forMonth($mes),
            "REPORTE {$tag} ESCALL.xlsx"
        );
    }

    public function exportDataCsv(Request $r)
    {
        $tag = $r->query('tag', 'OCTUBRE25');
        $mes = $this->assertMonth($r->query('mes'));
        [$start, $end] = $this->monthRange($mes);

        [$sql, $bindings] = $this->sqlReporteData($start, $end);
        $this->tuneDbForStreaming();

        return response()->streamDownload(function () use ($sql, $bindings) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, self::HEADERS_REPORTE_DATA);

            foreach (DB::connection()->cursor($sql, $bindings) as $row) {
                $r = (array) $row;

                fputcsv($out, [
                    $r['CARTERA'] ?? '',
                    $r['DNI'] ?? '',
                    $r['TITULAR'] ?? '',
                    $r['CODIGO'] ?? '',
                    $r['ENTIDAD'] ?? '',
                    $r['COSECHA'] ?? '',
                    $r['PRODUCTO'] ?? '',
                    $r['SUB_PRODUCTO'] ?? '',
                    $r['HISTORICO'] ?? '',
                    $r['DEPARTAMENTO'] ?? '',
                    $r['RANGO'] ?? '',
                    $r['DEUDA TOTAL'] ?? null,
                    $r['DEUDA CAPITAL'] ?? null,
                    $r['CAMPAÑA'] ?? null,
                    $r['%'] ?? null,
                    $r['MC'] ?? '',
                    $r['SITUACION'] ?? '',
                    $r['TELEFONOS'] ?? '',
                    $r['TELEFONO - ULTIMA GESTION'] ?? '',
                    $r['STATUS - ULTIMA GESTION'] ?? '',
                    $r['TIPIFICACION - ULTIMA GESTION'] ?? '',
                    $r['OBSERVACION - ULTIMA GESTION'] ?? '',
                    $r['FECHA - ULTIMA GESTION'] ?? '',
                    $r['TELEFONO - MEJOR GESTION'] ?? '',
                    $r['STATUS - MEJOR GESTION'] ?? '',
                    $r['TIPIFICACION - MEJOR GESTION'] ?? '',
                    $r['OBSERVACION - MEJOR GESTION'] ?? '',
                    $r['FECHA - MEJOR GESTION'] ?? '',
                    $r['INTENSIDAD'] ?? 0,
                ]);
            }

            fclose($out);
        }, "REPORTE {$tag} ESCALL.csv", [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportDataXlsxFast(Request $r)
    {
        $tag = $r->query('tag', 'DICIEMBRE');
        $mes = $this->assertMonth($r->query('mes'));
        [$start, $end] = $this->monthRange($mes);

        [$sql, $bindings] = $this->sqlReporteData($start, $end);
        $this->tuneDbForStreaming();

        $file = "REPORTE {$tag} ESCALL.xlsx";

        return response()->streamDownload(function () use ($sql, $bindings) {
            $txt = fn($v) => $v === null ? '' : (string) $v;
            $num = function ($v) {
                if ($v === null || $v === '') return null;
                $v = str_replace(',', '', (string)$v);
                return (float) $v;
            };

            $tmp = tempnam(sys_get_temp_dir(), 'reporte_');
            $tmpXlsx = $tmp . '.xlsx';
            @rename($tmp, $tmpXlsx);

            $writer = WriterFactory::createFromFile($tmpXlsx);
            $writer->openToFile($tmpXlsx);

            $writer->addRow(Row::fromValues(self::HEADERS_REPORTE_DATA));

            foreach (DB::connection()->cursor($sql, $bindings) as $row) {
                $r = (array) $row;

                $writer->addRow(Row::fromValues([
                    $txt($r['CARTERA'] ?? ''),
                    $txt($r['DNI'] ?? ''),
                    $txt($r['TITULAR'] ?? ''),
                    $txt($r['CODIGO'] ?? ''),
                    $txt($r['ENTIDAD'] ?? ''),
                    $txt($r['COSECHA'] ?? ''),
                    $txt($r['PRODUCTO'] ?? ''),
                    $txt($r['SUB_PRODUCTO'] ?? ''),
                    $txt($r['HISTORICO'] ?? ''),
                    $txt($r['DEPARTAMENTO'] ?? ''),
                    $txt($r['RANGO'] ?? ''),
                    $num($r['DEUDA TOTAL'] ?? null),
                    $num($r['DEUDA CAPITAL'] ?? null),
                    $num($r['CAMPAÑA'] ?? null),
                    $num($r['%'] ?? null),
                    $txt($r['MC'] ?? ''),
                    $txt($r['SITUACION'] ?? ''),
                    $txt($r['TELEFONOS'] ?? ''),
                    $txt($r['TELEFONO - ULTIMA GESTION'] ?? ''),
                    $txt($r['STATUS - ULTIMA GESTION'] ?? ''),
                    $txt($r['TIPIFICACION - ULTIMA GESTION'] ?? ''),
                    $txt($r['OBSERVACION - ULTIMA GESTION'] ?? ''),
                    $txt($r['FECHA - ULTIMA GESTION'] ?? ''),
                    $txt($r['TELEFONO - MEJOR GESTION'] ?? ''),
                    $txt($r['STATUS - MEJOR GESTION'] ?? ''),
                    $txt($r['TIPIFICACION - MEJOR GESTION'] ?? ''),
                    $txt($r['OBSERVACION - MEJOR GESTION'] ?? ''),
                    $txt($r['FECHA - MEJOR GESTION'] ?? ''),
                    (int) ($r['INTENSIDAD'] ?? 0),
                ]));
            }

            $writer->close();

            readfile($tmpXlsx);
            @unlink($tmpXlsx);
        }, $file, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportAsignacionTec(Request $r)
    {
        $lote = $r->query('lote', '1809');

        return Excel::download(
            new AsignacionTecCenterPlaceholderExport,
            "FRMT_RG AGENCIAS EXTERNAS {$lote}.xlsx"
        );
    }

    public function exportDataTecCenter(Request $r)
    {
        $mes = $this->assertMonth($r->query('mes'));

        return Excel::download(
            (new ReporteDataTecCenterMesExport)->forMonth($mes),
            "REPORTE DATA TEC CENTER {$mes}.xlsx"
        );
    }

    public function exportTecCenterData(Request $r)
    {
        $mes = $this->assertMonth($r->query('mes', Carbon::now()->format('Y-m')));
        $suf = Carbon::createFromFormat('Y-m', $mes)->format('d.m');

        return Excel::download(
            (new ReporteTecCenterMesExport)->forMonth($mes),
            "FRMT_GESTIONES CP - 03.01 ({$suf}).xlsx"
        );
    }

    private function assertMonth(?string $mes): string
    {
        $mes = (string) $mes;
        if (!preg_match('/^\d{4}\-\d{2}$/', $mes)) {
            abort(422, 'Mes inválido. Use formato YYYY-MM.');
        }
        return $mes;
    }

    private function monthRange(string $mes): array
    {
        $start = Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->addMonth()->toDateString();
        return [$start, $end];
    }

    private function tuneDbForStreaming(): void
    {
        DB::connection()->disableQueryLog();

        try {
            $pdo = DB::connection()->getPdo();
            @$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        } catch (\Throwable $e) {
        }
    }

    private function sqlReporteData(string $start, string $end): array
    {
        $sql = <<<SQL
WITH
dset AS (
  SELECT DISTINCT dni FROM data
),
best_all AS (
  SELECT
    g.dni,
    g.tipificacion,
    g.telefono,
    COALESCE(t.puntos, 999) AS puntos,
    t.mc,
    g.fecha_gestion,
    ROW_NUMBER() OVER (
      PARTITION BY g.dni
      ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
    ) AS rn
  FROM gestiones g
  JOIN dset ds ON ds.dni = g.dni
  LEFT JOIN tipificaciones t
    ON REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')
    =
       REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')
  WHERE (g.fecha_gestion < ? OR g.fecha_gestion >= ?)
),
best AS (SELECT * FROM best_all WHERE rn = 1),

ultima_all AS (
  SELECT
    g.dni,
    g.telefono      AS u_tel,
    g.status        AS u_status,
    g.tipificacion  AS u_tip,
    g.observacion   AS u_obs,
    g.fecha_gestion AS u_fecha,
    ROW_NUMBER() OVER (
      PARTITION BY g.dni
      ORDER BY g.fecha_gestion DESC
    ) AS rn
  FROM gestiones g
  JOIN dset ds ON ds.dni = g.dni
  WHERE g.fecha_gestion >= ? AND g.fecha_gestion < ?
),
u AS (SELECT * FROM ultima_all WHERE rn = 1),

mejor_all AS (
  SELECT
    g.dni,
    g.telefono      AS m_tel,
    g.status        AS m_status,
    g.tipificacion  AS m_tip,
    g.observacion   AS m_obs,
    g.fecha_gestion AS m_fecha,
    COALESCE(t.puntos,999) AS m_puntos,
    ROW_NUMBER() OVER (
      PARTITION BY g.dni
      ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
    ) AS rn
  FROM gestiones g
  JOIN dset ds ON ds.dni = g.dni
  LEFT JOIN tipificaciones t
    ON REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')
    =
       REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')
  WHERE g.fecha_gestion >= ? AND g.fecha_gestion < ?
),
m AS (SELECT * FROM mejor_all WHERE rn = 1),

cnt AS (
  SELECT g.dni, COUNT(*) AS intensidad
  FROM gestiones g
  JOIN dset ds ON ds.dni = g.dni
  WHERE g.fecha_gestion >= ? AND g.fecha_gestion < ?
  GROUP BY g.dni
)

SELECT
  d.cartera                               AS `CARTERA`,
  COALESCE(CAST(d.dni    AS CHAR), '')    AS `DNI`,
  d.titular                               AS `TITULAR`,
  COALESCE(CAST(d.codigo AS CHAR), '')    AS `CODIGO`,
  d.entidad                               AS `ENTIDAD`,
  d.cosecha                               AS `COSECHA`,
  d.producto                              AS `PRODUCTO`,
  d.sub_producto                          AS `SUB_PRODUCTO`,
  d.historico                             AS `HISTORICO`,
  d.departamento                          AS `DEPARTAMENTO`,
  CASE
    WHEN COALESCE(d.deuda_capital,0) >= 50000 THEN '1.[50K - MAS]'
    WHEN COALESCE(d.deuda_capital,0) >= 20000 THEN '2.[20K - 50K]'
    WHEN COALESCE(d.deuda_capital,0) >= 10000 THEN '3.[10K - 20K]'
    WHEN COALESCE(d.deuda_capital,0) >=  5000 THEN '4.[5K - 10K]'
    WHEN COALESCE(d.deuda_capital,0) >=  1000 THEN '5.[1K - 5K]'
    WHEN COALESCE(d.deuda_capital,0) >=   501 THEN '6.[501 - 1K]'
    ELSE '7.[0 - 500]'
  END                                     AS `RANGO`,
  d.deuda_total                           AS `DEUDA TOTAL`,
  d.deuda_capital                         AS `DEUDA CAPITAL`,
  d.campania                              AS `CAMPAÑA`,
  d.porcentaje                            AS `%`,
  COALESCE(best.mc, 'SG')                 AS `MC`,
  COALESCE(best.tipificacion,'SIN GESTION') AS `SITUACION`,
  COALESCE(CAST(best.telefono AS CHAR), '') AS `TELEFONOS`,
  COALESCE(CAST(u.u_tel     AS CHAR), '') AS `TELEFONO - ULTIMA GESTION`,
  u.u_status                              AS `STATUS - ULTIMA GESTION`,
  u.u_tip                                 AS `TIPIFICACION - ULTIMA GESTION`,
  u.u_obs                                 AS `OBSERVACION - ULTIMA GESTION`,
  u.u_fecha                               AS `FECHA - ULTIMA GESTION`,
  COALESCE(CAST(m.m_tel     AS CHAR), '') AS `TELEFONO - MEJOR GESTION`,
  m.m_status                              AS `STATUS - MEJOR GESTION`,
  m.m_tip                                 AS `TIPIFICACION - MEJOR GESTION`,
  m.m_obs                                 AS `OBSERVACION - MEJOR GESTION`,
  m.m_fecha                               AS `FECHA - MEJOR GESTION`,
  COALESCE(cnt.intensidad,0)              AS `INTENSIDAD`
FROM data d
LEFT JOIN best ON best.dni = d.dni
LEFT JOIN u    ON u.dni    = d.dni
LEFT JOIN m    ON m.dni    = d.dni
LEFT JOIN cnt  ON cnt.dni  = d.dni
ORDER BY d.cartera, d.dni
SQL;

        $bindings = [$start, $end, $start, $end, $start, $end, $start, $end];
        return [$sql, $bindings];
    }
}
