<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDailyGestionesReport extends Command
{
    protected $signature = 'gestiones:sync-daily-report';

    protected $description = 'Sincroniza llamadas, luego gestiones manuales y finalmente envía el reporte diario.';

    public function handle(): int
    {
        $this->info('1/3 Sincronizando llamadas del discador.');
        if ($this->call('llamadas:sync-sp-hourly') !== self::SUCCESS) {
            $this->error('No se enviará el correo porque falló la sincronización de llamadas.');

            return self::FAILURE;
        }

        $this->info('2/3 Sincronizando gestiones manuales y enviando el correo.');

        return $this->call('gestiones:sync-sp-hourly', ['--send-mail' => true]);
    }
}
