<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

class ReporteTecCenterXlsxFastExport
{
    private const HEADERS = [
        'DNI','N° CUENTA','CARTERA','FECHA GESTION','CONTACTO',
        'RESULTADO','GESTOR','COMENTARIO','TELEFONO'
    ];

    private string $fi; // YYYY-MM-DD
    private string $ff; // YYYY-MM-DD

    public function forRange(string $fi, string $ff): self
    {
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $fi) || !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $ff)) {
            throw new \InvalidArgumentException('Fechas inválidas. Formato esperado: YYYY-MM-DD');
        }
        $this->fi = $fi;
        $this->ff = $ff;
        return $this;
    }

    /** Descarga HTTP */
    public function stream(string $filename)
    {
        $this->tuneDbForStreaming();

        return response()->streamDownload(function () {
            $tmp = tempnam(sys_get_temp_dir(), 'teccenter_');
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

    /** Para comandos (Drive, mail, etc): guarda en disco */
    public function saveTo(string $path): void
    {
        $this->tuneDbForStreaming();
        $this->writeToXlsx($path);
    }

    /** Para adjuntos: binario XLSX */
    public function toBinary(): string
    {
        $this->tuneDbForStreaming();

        $tmp = tempnam(sys_get_temp_dir(), 'teccenter_');
        $tmpXlsx = $tmp . '.xlsx';
        @rename($tmp, $tmpXlsx);

        $this->writeToXlsx($tmpXlsx);

        $bin = file_get_contents($tmpXlsx) ?: '';
        @unlink($tmpXlsx);

        return $bin;
    }

    private function writeToXlsx(string $path): void
    {
        $start = $this->fi . ' 00:00:00';
        $endEx = Carbon::parse($this->ff)->addDay()->startOfDay()->toDateTimeString();

        $writer = WriterFactory::createFromFile($path);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues(self::HEADERS));

        $q = $this->baseQuery($start, $endEx);

        $txt = fn($v) => $v === null ? '' : (string)$v;

        $dmy = function ($v): string {
            if (!$v) return '';
            try { return Carbon::parse($v)->format('d/m/Y'); } catch (\Throwable $e) { return ''; }
        };

        foreach ($q->cursor() as $r) {
            $writer->addRow(Row::fromValues([
                $txt($r->dni ?? ''),           // DNI (texto)
                $txt($r->ncuenta ?? ''),       // N° CUENTA (texto)
                $txt($r->cartera ?? ''),       // CARTERA (renombrada)
                $dmy($r->fecha_gestion ?? null),
                $txt($r->contacto ?? ''),      // CONTACTO (desde tabla)
                $txt($r->resultado ?? ''),     // RESULTADO (desde tabla)
                $txt($r->gestor ?? ''),
                $txt($r->comentario ?? ''),
                $txt($r->telefono ?? ''),      // TELEFONO (texto)
            ]));
        }

        $writer->close();
    }

    private function baseQuery(string $start, string $endEx)
    {
        // Normalización para matchear tipificación con la tabla
        $normG = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $normT = "REPLACE(REPLACE(UPPER(TRIM(tt.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->leftJoin('tipificaciones_teccenter as tt', DB::raw($normG), '=', DB::raw($normT))
            ->where('d.cartera', '=', 'TEC CENTER')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx)
            ->selectRaw("
                COALESCE(CAST(g.dni AS CHAR), '') AS dni,
                COALESCE(CAST(d.codigo AS CHAR), '') AS ncuenta,

                CASE
                    WHEN d.cosecha = 'BBVA' THEN 'BBVA'
                    WHEN d.cosecha = 'IBK TEC 01' THEN 'IBK PROPIAS TEC 01'
                    WHEN d.cosecha = 'IBK TEC 02' THEN 'IBK PROPIAS TEC 02'
                    WHEN d.cosecha = 'IBK TEC 03' THEN 'IBK PROPIAS TEC 03'
                    WHEN d.cosecha = 'IBK TEC 04' THEN 'IBK PROPIAS TEC 04'
                    ELSE COALESCE(d.cosecha,'')
                END AS cartera,

                g.fecha_gestion AS fecha_gestion,

                COALESCE(tt.contacto, g.status, '') AS contacto,
                COALESCE(tt.resultado, g.tipificacion, '') AS resultado,

                COALESCE(NULLIF(TRIM(g.nombre),''),'SIN NOMBRE') AS gestor,
                g.observacion AS comentario,
                COALESCE(CAST(g.telefono AS CHAR), '') AS telefono
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
        } catch (\Throwable $e) {
            // no-op
        }
    }
}
