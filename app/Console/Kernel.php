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

        // Cada hora (minuto 0), EXCEPTO a las 13:00
        $schedule->command('gestiones:sync-sp-hourly')
            ->hourlyAt(0)
            ->timezone($tz)
            ->when(fn () => now()->timezone($tz)->hour !== 13)
            ->name('gestiones_sync')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/gestiones_sync.log'));

        // Todos los días a las 13:00: sync + correo
        $schedule->command('gestiones:sync-sp-hourly --send-mail')
            ->dailyAt('19:00')
            ->timezone($tz)
            ->name('gestiones_sync')
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
