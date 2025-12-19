<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

class ReporteImpulseXlsxFastExport
{
    private const HEADERS = [
        'DOCUMENTO','CLIENTE','ACCIÓN','CONTACTO','AGENTE','OPERACION','ENTIDAD','EQUIPO',
        'FECHA GESTION','FECHA CITA','TELEFONO','OBSERVACION',
        'MONTO PROMESA','NRO CUOTAS','FECHA PROMESA','PROCEDENCIA LLAMADA',
    ];

    private string $fi;     // YYYY-MM-DD
    private string $ff;     // YYYY-MM-DD
    private int $equipo = 2;

    public function forRange(string $fi, string $ff, int $equipo = 2): self
    {
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $fi) || !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $ff)) {
            throw new \InvalidArgumentException('Fechas inválidas. Formato esperado: YYYY-MM-DD');
        }

        if (!in_array($equipo, [2, 3], true)) $equipo = 2;

        $this->fi = $fi;
        $this->ff = $ff;
        $this->equipo = $equipo;

        return $this;
    }

    /** Para descargas HTTP (igual que CarterasXlsxFast) */
    public function stream(string $filename)
    {
        $this->tuneDbForStreaming();

        return response()->streamDownload(function () {
            $tmp = tempnam(sys_get_temp_dir(), 'impulse_');
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

    /** Para adjuntar en correos: devuelve el binario XLSX */
    public function toBinary(): string
    {
        $this->tuneDbForStreaming();

        $tmp = tempnam(sys_get_temp_dir(), 'impulse_');
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

        // Headings
        $writer->addRow(Row::fromValues(self::HEADERS));

        $q = $this->baseQuery($start, $endEx);

        $txt = fn($v) => $v === null ? '' : (string)$v;
        $num = function ($v) {
            if ($v === null || $v === '') return null;
            $v = str_replace(',', '', (string)$v);
            return is_numeric($v) ? (float)$v : null;
        };
        $dmy = function ($v): string {
            if (!$v) return '';
            try { return Carbon::parse($v)->format('d/m/Y'); } catch (\Throwable $e) { return ''; }
        };

        foreach ($q->cursor() as $r) {
            // Etiqueta equipo (como tu map anterior)
            $equipoLabel = ($r->entidad === 'BCP') ? 'PROPIA 3 - ESCALL' : 'PROPIA 2 - ESCALL';

            $writer->addRow(Row::fromValues([
                $txt($r->documento ?? ''),         // DOCUMENTO (texto)
                $txt($r->cliente ?? ''),
                $txt($r->accion ?? ''),
                $txt($r->contacto ?? ''),
                $txt($r->agente ?? ''),
                $txt($r->operacion ?? ''),         // OPERACION (texto)
                $txt($r->entidad ?? ''),
                $txt($equipoLabel),                // EQUIPO

                $dmy($r->fecha_gestion ?? null),   // FECHA GESTION (texto)
                '',                                // FECHA CITA (vacío)
                $txt($r->telefono ?? ''),          // TELEFONO (texto)
                $txt($r->observacion ?? ''),

                $num($r->monto_promesa ?? null),   // MONTO PROMESA (num)
                (int)($r->nro_cuotas ?? 0),         // NRO CUOTAS (int)
                $dmy($r->fecha_promesa ?? null),   // FECHA PROMESA (texto)
                'Predictivo',                      // PROCEDENCIA LLAMADA
            ]));
        }

        $writer->close();
    }

    private function baseQuery(string $start, string $endEx)
    {
        // Normalización igual que usas en otros reportes (evita saltos de línea y espacios raros)
        $normG  = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $normTI = "REPLACE(REPLACE(UPPER(TRIM(ti.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        $q = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->leftJoin('tipificaciones_impulsego as ti', DB::raw($normG), '=', DB::raw($normTI))
            ->where('d.cartera', 'like', '%IMPULSE%')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx)
            ->selectRaw("
                g.dni AS documento,
                d.titular AS cliente,

                COALESCE(ti.accion, g.tipificacion)  AS accion,
                COALESCE(ti.contacto, g.status)      AS contacto,

                COALESCE(NULLIF(TRIM(g.nombre),''),'SIN NOMBRE') AS agente,
                d.codigo AS operacion,
                d.entidad AS entidad,
                g.fecha_gestion AS fecha_gestion,
                g.telefono AS telefono,
                g.observacion AS observacion,
                g.monto_pago AS monto_promesa,
                CASE WHEN g.monto_pago IS NULL OR g.monto_pago=0 THEN 0 ELSE 1 END AS nro_cuotas,
                g.fecha_pago AS fecha_promesa
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni');

        // Filtro por equipo (igual que tu export anterior)
        if ($this->equipo === 3) {
            $q->where('d.entidad', 'BCP');
        } else { // equipo 2
            $q->where(function ($qq) {
                $qq->whereNull('d.entidad')
                ->orWhere('d.entidad', '!=', 'BCP');
            });
        }

        return $q;
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
