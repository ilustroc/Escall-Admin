<?php

namespace App\Console\Commands;

use App\Exports\ReporteKpInvestXlsxFastExport;
use App\Mail\ReporteGestionesKpInvestMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendKpInvestWeeklyMail extends Command
{
    protected $signature = 'gestiones:mail-kpinvest-weekly {--dry-run}';
    protected $description = 'Envía reporte semanal KP INVEST (Lun-Sáb) con XLSX adjunto.';

    public function handle(): int
    {
        $tz = 'America/Lima';
        $now = now()->timezone($tz);

        // Semana Lun-Sáb: inicio = lunes, fin = hoy (sábado en schedule)
        $fi = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $ff = $now->toDateString();

        $tos = config('mailing.kpinvest_to', []);
        if (empty($tos)) {
            $this->warn('KPINVEST_MAIL_TO vacío (config). No se envía correo.');
            return self::SUCCESS;
        }

        $diaIni = Carbon::parse($fi, $tz)->format('d/m/Y');
        $diaFin = Carbon::parse($ff, $tz)->format('d/m/Y');
        $file   = 'Gestiones KP INVEST - Escall ' .
                  Carbon::parse($fi, $tz)->format('Ymd') . '-' .
                  Carbon::parse($ff, $tz)->format('Ymd') . '.xlsx';

        if ($this->option('dry-run')) {
            $this->info("DRY RUN: Enviaría a: " . implode(', ', $tos));
            $this->info("Rango: {$fi} -> {$ff}");
            $this->info("Archivo: {$file}");
            return self::SUCCESS;
        }

        try {
            $bin = (new ReporteKpInvestXlsxFastExport())->forRange($fi, $ff)->toBinary();

            Mail::to($tos)->send(new ReporteGestionesKpInvestMail(
                fechaInicio: $diaIni,
                fechaFin: $diaFin,
                filename: $file,
                data: $bin
            ));

            $this->info("Email KP INVEST enviado a: " . implode(', ', $tos));
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Falló el envío KP INVEST: " . $e->getMessage());
            Log::error('KPInvest weekly mail failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
