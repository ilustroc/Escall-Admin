<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $tz = 'America/Lima';

        // Cada hora (minuto 0), EXCEPTO a las 19:00
        $schedule->command('gestiones:sync-sp-hourly')
            ->hourlyAt(0)
            ->timezone($tz)
            ->when(fn () => now()->timezone($tz)->hour !== 19)
            ->name('gestiones_sync_hourly')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));

        // Lunes a Sábado 19:00: sync + correo (IMPULSE + TEC CENTER dentro del mismo --send-mail)
        $schedule->command('gestiones:sync-sp-hourly --send-mail')
            ->cron('0 19 * * 1-6') // 19:00, Lun-Sab (más seguro que days())
            ->timezone($tz)
            ->name('gestiones_sync_daily_mail')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));

        // Cada sábado 19:30: reporte semanal KP INVEST
        $schedule->command('gestiones:mail-kpinvest-weekly')
            ->weeklyOn(6, '19:30') // 6 = sábado
            ->timezone($tz)
            ->name('gestiones_kpinvest_weekly_mail')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));

        // Subir RPT_GESTIONES a Drive (Lun-Sab) después del correo (recomendado 19:15)
        $schedule->command('drive:rpt-gestiones-upload')
            ->cron('30 19 * * 1-6') // 19:30, Lun-Sab
            ->timezone($tz)
            ->name('drive_rpt_gestiones_upload')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/rpt_gestiones_drive.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
