<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDO;

class SyncGestionesSpHourly extends Command
{
    protected $signature = 'gestiones:sync-sp-hourly
                            {--dry-run : No inserta, solo muestra el rango}';

    protected $description = 'Cada hora elimina e importa nuevamente las gestiones del día (fi=hoy, ff=mañana).';

    public function handle(): int
    {
        $tz = 'America/Lima';

        // Día actual
        $today = now()->timezone($tz)->toDateString(); // YYYY-mm-dd

        // SP requiere rango tipo: [fi, ff) => ff debe ser el día siguiente
        $fi = $today;
        $ff = Carbon::parse($today, $tz)->addDay()->toDateString();

        $this->info("Sincronizando gestiones del dia: {$today}");
        $this->info("Rango SP: {$fi} -> {$ff}");

        if ($this->option('dry-run')) {
            $this->info("DRY RUN (sin eliminar ni insertar).");
            return self::SUCCESS;
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        DB::connection()->disableQueryLog();

        // 1) Borrar todas las gestiones del día en local (tu columna es DATE)
        $deleted = DB::table('gestiones')
            ->where('fecha_gestion', $today)
            ->delete();

        $this->info("Eliminadas en local: {$deleted}");

        // 2) Insertar desde SP en streaming
        $inserted = $this->streamFromSpAndInsert($fi, $ff);

        $this->info("Insertadas: {$inserted}");
        return self::SUCCESS;
    }

    private function streamFromSpAndInsert(string $fi, string $ff): int
    {
        $pdo = DB::connection('sp')->getPdo();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $stmt = $pdo->prepare('CALL sp_gestiones(?, ?)');
        $stmt->execute([$fi, $ff]);

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
        $norm = function (string $k): string {
            $k = mb_strtolower($k, 'UTF-8');
            $k = strtr($k, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
            $k = preg_replace('/[^a-z0-9]+/', '_', $k);
            return trim($k, '_');
        };

        $x = [];
        foreach ($r as $k => $v) $x[$norm($k)] = $v;

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

        // Guardamos SOLO fecha porque tu columna fecha_gestion es DATE
        $fg = $this->toDate($fechaGestion);
        if (!$fg || !$dni) return null;

        $fp = $this->toDate($fechaPago);

        return [
            'fecha_gestion' => $fg,
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

    private function toDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);

        if ($s === '' || $s === '0000-00-00' || $s === '00/00/0000' || $s === '00000000') return null;

        // yyyy-mm-dd o yyyy-mm-dd hh:mm:ss
        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $s)) {
            return substr($s, 0, 10);
        }

        // dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            $y  = (int)$m[3];
            $mo = (int)$m[2];
            $d  = (int)$m[1];
            if ($y < 1900 || $y > 2100) return null;
            if (!checkdate($mo, $d, $y)) return null;
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
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
