<?php

namespace App\Exports;

use App\Queries\Reportes\ReporteImpulseQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

class ReporteImpulseXlsxFastExport
{
    private const HEADERS = [
        'DOCUMENTO', 'CLIENTE', 'ACCIÓN', 'CONTACTO', 'AGENTE', 'OPERACION', 'ENTIDAD', 'EQUIPO',
        'FECHA GESTION', 'FECHA CITA', 'TELEFONO', 'OBSERVACION',
        'MONTO PROMESA', 'NRO CUOTAS', 'FECHA PROMESA', 'PROCEDENCIA LLAMADA',
    ];

    private string $fi;     // YYYY-MM-DD

    private string $ff;     // YYYY-MM-DD

    private int $equipo = 2;

    public function forRange(string $fi, string $ff, int $equipo = 2): self
    {
        if (! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $fi) || ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $ff)) {
            throw new \InvalidArgumentException('Fechas inválidas. Formato esperado: YYYY-MM-DD');
        }

        if (! in_array($equipo, [2, 3], true)) {
            $equipo = 2;
        }

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
            $tmpXlsx = $tmp.'.xlsx';
            @rename($tmp, $tmpXlsx);

            $this->writeToXlsx($tmpXlsx);

            readfile($tmpXlsx);
            @unlink($tmpXlsx);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** Para adjuntar en correos: devuelve el binario XLSX */
    public function toBinary(): string
    {
        $this->tuneDbForStreaming();

        $tmp = tempnam(sys_get_temp_dir(), 'impulse_');
        $tmpXlsx = $tmp.'.xlsx';
        @rename($tmp, $tmpXlsx);

        $this->writeToXlsx($tmpXlsx);

        $bin = file_get_contents($tmpXlsx) ?: '';
        @unlink($tmpXlsx);

        return $bin;
    }

    private function writeToXlsx(string $path): void
    {
        $writer = WriterFactory::createFromFile($path);
        $writer->openToFile($path);

        // Headings
        $writer->addRow(Row::fromValues(self::HEADERS));

        $q = app(ReporteImpulseQuery::class)->builder($this->fi, $this->ff, $this->equipo);

        $txt = fn ($v) => $v === null ? '' : (string) $v;
        $num = function ($v) {
            if ($v === null || $v === '') {
                return null;
            }
            $v = str_replace(',', '', (string) $v);

            return is_numeric($v) ? (float) $v : null;
        };
        $dmy = function ($v): string {
            if (! $v) {
                return '';
            }
            try {
                return Carbon::parse($v)->format('d/m/Y');
            } catch (\Throwable $e) {
                return '';
            }
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
                (int) ($r->nro_cuotas ?? 0),         // NRO CUOTAS (int)
                $dmy($r->fecha_promesa ?? null),   // FECHA PROMESA (texto)
                'Predictivo',                      // PROCEDENCIA LLAMADA
            ]));
        }

        $writer->close();
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
