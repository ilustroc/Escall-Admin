<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Exports\ReporteTecCenterXlsxFastExport;
use Symfony\Component\Process\Process;

class UploadRptGestionesDailyRclone extends Command
{
    protected $signature = 'drive:rpt-gestiones-upload
        {--date= : YYYY-MM-DD (opcional para pruebas)}
        {--dry-run : No sube, solo genera y muestra comandos}';

    protected $description = 'Genera XLSX y lo sube a Google Drive (Compartido conmigo) via rclone';

    public function handle(): int
    {
        $tz = 'America/Lima';

        $dateOpt = $this->option('date');
        $day = $dateOpt
            ? Carbon::createFromFormat('Y-m-d', $dateOpt, $tz)
            : now()->timezone($tz);

        $fi = $day->toDateString();
        $ff = $fi;

        $year = $day->format('Y');
        $month = $this->monthEsUpper((int)$day->format('m')); // ENERO..DICIEMBRE
        $ddmm = $day->format('d.m');

        // Nombre final
        $filename = "FRMT_GESTIONES CP - {$ddmm}.xlsx";

        // Generar XLSX local (temporal)
        $rel = "tmp/{$filename}";
        $abs = storage_path("app/{$rel}");
        if (!is_dir(dirname($abs))) @mkdir(dirname($abs), 0775, true);

        (new ReporteTecCenterXlsxFastExport())
            ->forRange($fi, $ff)
            ->saveTo($abs);
        
        // Rclone config
        $bin    = env('RCLONE_BIN', 'rclone');
        $config = env('RCLONE_CONFIG'); // opcional pero recomendado
        $remote = env('RCLONE_REMOTE', 'escall:');
        $root   = trim(env('RCLONE_ROOT_FOLDER', 'Escall Peru/RPT_GESTIONES'), '/');

        // Destino: escall:Escall Peru/RPT_GESTIONES/2025/DICIEMBRE
        $remoteDir  = rtrim($remote, ':') . ':' . $root . "/{$year}/{$month}";
        $remoteFile = $remoteDir . "/{$filename}";

        try {
            // 1) mkdir (crea toda la ruta si falta)
            $cmdMkdir = $this->rcloneCmd($bin, $config, [
                'mkdir',
                $remoteDir,
                '--drive-shared-with-me',
            ]);

            // 2) copyto (sube como archivo exacto, reemplaza si existe)
            $cmdCopy = $this->rcloneCmd($bin, $config, [
                'copyto',
                $abs,
                $remoteFile,
                '--drive-shared-with-me',
            ]);

            if ($this->option('dry-run')) {
                $this->info("DRY RUN:");
                $this->line("mkdir => " . $this->cmdToString($cmdMkdir));
                $this->line("copyto => " . $this->cmdToString($cmdCopy));
                return self::SUCCESS;
            }

            $this->runProcess($cmdMkdir, 180);
            $this->runProcess($cmdCopy, 600);

            $this->info("Subido: {$remoteFile}");
            Log::info('RPT_GESTIONES upload OK', compact('remoteFile','fi','ff'));

        } catch (\Throwable $e) {
            $this->error("Falló upload: " . $e->getMessage());
            Log::error('RPT_GESTIONES upload failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        } finally {
            @unlink($abs);
        }

        return self::SUCCESS;
    }

    private function runProcess(array $cmd, int $timeoutSeconds): void
    {
        $p = new Process($cmd);
        $p->setTimeout($timeoutSeconds);
        $p->run();

        if (!$p->isSuccessful()) {
            $out = trim($p->getOutput());
            $err = trim($p->getErrorOutput());
            throw new \RuntimeException("rclone falló.\nOUT: {$out}\nERR: {$err}");
        }
    }

    private function rcloneCmd(string $bin, ?string $config, array $args): array
    {
        $cmd = [$bin];

        // Usa el mismo config que tu rclone en SSH (clave para que funcione en cron)
        if ($config) {
            $cmd[] = '--config';
            $cmd[] = $config;
        }

        return array_merge($cmd, $args);
    }

    private function cmdToString(array $cmd): string
    {
        // Solo para imprimir en dry-run (no ejecutar)
        return implode(' ', array_map(fn($x) => str_contains($x, ' ') ? "\"{$x}\"" : $x, $cmd));
    }

    private function monthEsUpper(int $m): string
    {
        return [
            1=>'ENERO', 2=>'FEBRERO', 3=>'MARZO', 4=>'ABRIL', 5=>'MAYO', 6=>'JUNIO',
            7=>'JULIO', 8=>'AGOSTO', 9=>'SEPTIEMBRE', 10=>'OCTUBRE', 11=>'NOVIEMBRE', 12=>'DICIEMBRE',
        ][$m] ?? (string)$m;
    }
}
