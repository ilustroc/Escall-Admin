<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

class ReporteKpInvestXlsxFastExport
{
    private const HEADERS = [
        'Fecha Gest','Tip.Doc','Num.Doc','Cliente','Teléfono','Acción',
        'Tipificación','ESTADO','Gestion','Fecha Promesa','Monto Promesa'
    ];

    private string $fi; // YYYY-MM-DD
    private string $ff; // YYYY-MM-DD

    public function forRange(string $fi, string $ff): self
    {
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $fi) || !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $ff)) {
            throw new \InvalidArgumentException('Fechas inválidas. Formato esperado: YYYY-MM-DD');
        }
        if ($ff < $fi) $ff = $fi;

        $this->fi = $fi;
        $this->ff = $ff;
        return $this;
    }

    /** Para adjuntar en correo */
    public function toBinary(): string
    {
        $this->tuneDbForStreaming();

        $tmp = tempnam(sys_get_temp_dir(), 'kpinvest_');
        $tmpXlsx = $tmp . '.xlsx';
        @rename($tmp, $tmpXlsx);

        $this->writeToXlsx($tmpXlsx);

        $bin = file_get_contents($tmpXlsx) ?: '';
        @unlink($tmpXlsx);

        return $bin;
    }

    /** Para descargas HTTP */
    public function stream(string $filename)
    {
        $this->tuneDbForStreaming();

        return response()->streamDownload(function () {
            $tmp = tempnam(sys_get_temp_dir(), 'kpinvest_');
            $tmpXlsx = $tmp . '.xlsx';
            @rename($tmp, $tmpXlsx);

            $this->writeToXlsx($tmpXlsx);

            readfile($tmpXlsx);
            @unlink($tmpXlsx);
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function writeToXlsx(string $path): void
    {
        $start = $this->fi . ' 00:00:00';
        $endEx = Carbon::parse($this->ff)->addDay()->startOfDay()->toDateTimeString();

        $writer = WriterFactory::createFromFile($path);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues(self::HEADERS));

        $q = $this->baseQuery($start, $endEx);

        // helpers
        $txt = fn($v) => $v === null ? '' : (string)$v;
        $dt  = function ($v): string {
            if (!$v) return '';
            try {
                // Fecha/hora legible (Excel lo suele reconocer)
                return Carbon::parse($v)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return '';
            }
        };
        $num = function ($v) {
            if ($v === null || $v === '') return null;
            $v = str_replace(',', '', (string)$v);
            return is_numeric($v) ? (float)$v : null;
        };

        foreach ($q->cursor() as $r) {
            $writer->addRow(Row::fromValues([
                $dt($r->fecha_gest ?? null),
                $txt($r->tip_doc ?? ''),
                $txt($r->num_doc ?? ''),     // TEXTO (DNI/RUC)
                $txt($r->cliente ?? ''),
                $txt($r->telefono ?? ''),    // TEXTO (TEL)
                $txt($r->accion ?? ''),
                $txt($r->tipificacion ?? ''),
                $txt($r->estado ?? ''),
                $txt($r->gestion ?? ''),
                $dt($r->fecha_promesa ?? null),
                $num($r->monto_promesa ?? null),
            ]));
        }

        $writer->close();
    }

    private function baseQuery(string $start, string $endEx)
    {
        // Normalización para empatar aunque haya espacios/saltos de línea
        $normG = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $normT = "REPLACE(REPLACE(UPPER(TRIM(tk.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->leftJoin('tipificaciones_kpinvest as tk', DB::raw($normG), '=', DB::raw($normT))
            ->where('d.cartera', '=', 'KP INVEST')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx)
            ->selectRaw("
                g.fecha_gestion                                              AS fecha_gest,
                CASE WHEN CHAR_LENGTH(g.dni)=8 THEN 'DNI'
                    WHEN CHAR_LENGTH(g.dni)=11 THEN 'RUC'
                    ELSE 'DNI' END                                          AS tip_doc,
                COALESCE(CAST(g.dni AS CHAR), '')                            AS num_doc,
                d.titular                                                    AS cliente,
                COALESCE(CAST(g.telefono AS CHAR), '')                       AS telefono,
                'LLAMADA ENTRANTE'                                           AS accion,

                -- 👇 Tipificación y Estado mapeados
                COALESCE(tk.tipificacion_kpinvest, g.tipificacion, '')       AS tipificacion,
                COALESCE(tk.estado, g.status, '')                            AS estado,

                g.observacion                                                AS gestion,
                g.fecha_pago                                                 AS fecha_promesa,
                g.monto_pago                                                 AS monto_promesa
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni');
    }


    private function tuneDbForStreaming(): void
    {
        DB::connection()->disableQueryLog();

        try {
            $pdo = DB::connection()->getPdo();
            @$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        } catch (\Throwable $e) {}
    }
}
