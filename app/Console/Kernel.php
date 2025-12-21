<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $tz = 'America/Lima';

        // Cada hora (minuto 0), EXCEPTO a las 19:00
        $schedule->command('gestiones:sync-sp-hourly')
            ->hourlyAt(0)
            ->timezone($tz)
            ->when(fn () => now()->timezone($tz)->hour !== 19)
            ->name('gestiones_sync')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));

        // Todos los días a las 19:00: sync + correo
        $schedule->command('gestiones:sync-sp-hourly --send-mail')
            ->dailyAt('19:00')
            ->timezone('America/Lima')
            ->days([1,2,3,4,5,6])
            ->name('gestiones_sync_daily_mail')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));

        //  Cada sábado a las 19:00: reporte semanal KP INVEST
        $schedule->command('gestiones:mail-kpinvest-weekly')
            ->weeklyOn(6, '19:00') // 6 = Saturday
            ->timezone($tz)
            ->name('gestiones_kpinvest_weekly_mail')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));
        }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
