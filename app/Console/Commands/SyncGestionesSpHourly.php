<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDO;

class SyncGestionesSpHourly extends Command
{
    protected $signature = 'gestiones:sync-sp-hourly
                            {--hours=2 : Ventana en horas (default 2)}
                            {--dry-run : No inserta, solo muestra el rango}';

    protected $description = 'Descarga gestiones desde SP cada hora usando una ventana deslizante de N horas.';

    public function handle(): int
    {
        $hours = (int)$this->option('hours');
        if ($hours < 1) $hours = 2;

        // Alinea a la hora exacta (si corre 11:03, toma 11:00)
        $to   = now()->timezone('America/Lima')->startOfHour();
        $from = (clone $to)->subHours(2);

        $this->info("📥 Descargando gestiones SP: {$from->toDateTimeString()} -> {$to->toDateTimeString()}");

        if ($this->option('dry-run')) {
            $this->warn("DRY RUN (sin insertar).");
            return self::SUCCESS;
        }

        @set_time_limit(0);
        @ini_set('memory_limit','1024M');
        DB::connection()->disableQueryLog();

        // 1) Borra ese rango local (para evitar duplicados por ventana solapada)
        DB::transaction(function () use ($from, $to) {
            DB::table('gestiones')
                ->whereBetween('fecha_gestion', [$from->toDateTimeString(), $to->toDateTimeString()])
                ->delete();
        });

        // 2) Inserta desde SP (streaming por lotes)
        $inserted = $this->streamFromSpAndInsert($from, $to);

        $this->info("✅ Listo. Insertadas: {$inserted}");
        return self::SUCCESS;
    }

    private function streamFromSpAndInsert(Carbon $from, Carbon $to): int
    {
        $pdo = DB::connection('sp')->getPdo();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        // OJO: tu SP debe aceptar DATETIME (o al menos strings con hora).
        // Si tu SP solo acepta DATE, aquí tendrás que crear otro SP o modificarlo.
        $stmt = $pdo->prepare('CALL sp_gestiones(?, ?)');
        $stmt->execute([$from->toDateTimeString(), $to->toDateTimeString()]);

        $batch = [];
        $BATCH = 2000;
        $inserted = 0;
        $now = now()->toDateTimeString();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mapped = $this->mapRow($row);
            if (!$mapped) continue;

            $mapped['created_at'] = $now;
            $mapped['updated_at'] = $now;

            $batch[] = $mapped;

            if (count($batch) >= $BATCH) {
                DB::table('gestiones')->insert($batch);
                $inserted += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('gestiones')->insert($batch);
            $inserted += count($batch);
        }

        while ($stmt->nextRowset()) { /* consume */ }
        $stmt->closeCursor();

        return $inserted;
    }

    private function mapRow(array $r): ?array
    {
        $norm = function(string $k): string {
            $k = mb_strtolower($k, 'UTF-8');
            $k = strtr($k, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
            $k = preg_replace('/[^a-z0-9]+/','_', $k);
            return trim($k,'_');
        };

        $x = [];
        foreach ($r as $k=>$v) $x[$norm($k)] = $v;

        $fechaGestion = $x['fecha_gestion'] ?? $x['fecha_de_gestion'] ?? null;
        $dni          = $x['dni'] ?? $x['documento'] ?? null;
        $telefono     = $x['telefono'] ?? $x['celular'] ?? null;
        $status       = $x['status'] ?? $x['tipo_de_contacto'] ?? $x['contacto'] ?? null;
        $tipificacion = $x['tipificacion'] ?? $x['tipo_de_resultado'] ?? $x['resultado'] ?? null;
        $observacion  = $x['observacion'] ?? $x['obs'] ?? null;
        $fechaPago    = $x['fecha_pago'] ?? null;
        $montoPago    = $x['monto_pago'] ?? $x['montopago'] ?? null;
        $nombre       = $x['nombre'] ?? $x['agente'] ?? $x['usuario'] ?? null;

        $status       = $this->defaultStatus($status);
        $tipificacion = $this->defaultTipificacion($tipificacion);

        // ✅ aquí guardamos DATETIME
        $fg = $this->toDateTime($fechaGestion);
        if (!$fg || !$dni) return null;

        $fp = $this->toDate($fechaPago);

        return [
            'fecha_gestion' => $fg, // DATETIME recomendado
            'dni'           => trim((string)$dni),
            'telefono'      => $this->cut($telefono, 25),
            'status'        => $this->cut($status, 100),
            'tipificacion'  => $this->cut($tipificacion, 120),
            'observacion'   => $this->cut($observacion, 500),
            'fecha_pago'    => $fp,
            'monto_pago'    => $this->toIntNullable($montoPago),
            'nombre'        => $this->cut($nombre, 150),
        ];
    }

    private function defaultStatus($v): string
    {
        $s = trim((string)$v);
        return $s === '' ? 'NO CONTACTO' : $s;
    }

    private function defaultTipificacion($v): string
    {
        $s = trim((string)$v);
        return $s === '' ? 'NO CONTESTA' : $s;
    }

    // ✅ DATETIME (Y-m-d H:i:s)
    private function toDateTime($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || str_starts_with($s, '0000-00-00')) return null;

        // yyyy-mm-dd hh:mm:ss
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $s)) {
            $s = str_replace('T',' ', $s);
            return $s;
        }

        // yyyy-mm-dd (lo convierte a 00:00:00)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s.' 00:00:00';
        }

        // dd/mm/yyyy hh:mm:ss
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3], $m[2], $m[1], $m[4], $m[5], $m[6]);
        }

        // dd/mm/yyyy -> 00:00:00
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    private function toDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '0000-00-00') return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) return substr($s, 0, 10);

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return null;
    }

    private function toIntNullable($v): ?int
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '?' || $s === '-') return null;
        $s = preg_replace('/[^\d\-]/', '', $s);
        return ($s === '' || !is_numeric($s)) ? null : (int)$s;
    }

    private function cut($v, int $n): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : mb_substr($s, 0, $n);
    }
}
