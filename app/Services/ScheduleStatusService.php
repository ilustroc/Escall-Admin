<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Cron\CronExpression;

class ScheduleStatusService
{
    public function get(): array
    {
        $tz  = 'America/Lima';
        $now = Carbon::now($tz);

        $tasks = [
            [
                'key'        => 'gestiones_sync_hourly',
                'label'      => 'Actualización de gestiones',
                'cron'       => '0 * * * *', // cada hora en el minuto 00
                'timezone'   => $tz,
                'note'       => 'El sistema se actualiza automáticamente con las gestiones más recientes para mantener la información al día.',
                'log_path'   => storage_path('logs/gestiones_sync.log'),
                'skip_hours' => [19],
                'group'      => 'Actualizaciones',
            ],
            [
                'key'      => 'gestiones_sync_daily_mail',
                'label'    => 'Reporte de gestiones a IMPULSE GO',
                'cron'     => '0 19 * * 1-6', // Lun–Sáb 19:00
                'timezone' => $tz,
                'note'     => 'Todos los días de lunes a sábado a las 7:00 p. m., el sistema actualiza la información y envía los reportes por correo automáticamente.',
                'log_path' => storage_path('logs/gestiones_sync.log'),
                'group'    => 'Reportes',
            ],
            [
                'key'      => 'gestiones_kpinvest_weekly_mail',
                'label'    => 'Reporte de gestiones a KP INVEST',
                'cron'     => '30 19 * * 6', // Sábado 19:30
                'timezone' => $tz,
                'note'     => 'Todos los sábados a las 7:30 p. m., el sistema envía automáticamente el reporte semanal de KP INVEST.',
                'log_path' => storage_path('logs/gestiones_sync.log'),
                'group'    => 'Reportes',
            ],
            [
                'key'      => 'drive_rpt_gestiones_upload',
                'label'    => 'Reporte de gestiones a TEC CENTER',
                'cron'     => '30 19 * * 1-6', // Lun–Sáb 19:30
                'timezone' => $tz,
                'note'     => 'De lunes a sábado a las 7:30 p. m., el sistema sube una copia del reporte de gestiones a Google Drive para que quede respaldado.',
                'log_path' => storage_path('logs/rpt_gestiones_drive.log'),
                'group'    => 'Respaldo',
            ],
        ];

        return array_map(function ($t) use ($now) {
            $tz = $t['timezone'] ?? config('app.timezone');
            $next = $this->nextRun($t['cron'], $now, $tz, $t['skip_hours'] ?? []);
            $last = $this->lastActivity($t['log_path'] ?? null, $tz);

            return array_merge($t, [
                'next_at'        => $next,
                'next_at_label'  => $next ? $next->format('d/m/Y H:i') : '—',
                'in'             => $next ? $this->humanCountdown($now, $next) : '—',
                'last_at'        => $last,
                'last_at_label'  => $last ? $last->format('d/m/Y H:i') : '—',
                'urgency'        => $next ? $this->urgency($now, $next) : 'muted', // muted|soon|now
            ]);
        }, $tasks);
    }

    private function nextRun(string $cron, Carbon $now, string $tz, array $skipHours = []): ?Carbon
    {
        try {
            $expr = CronExpression::factory($cron);
        } catch (\Throwable $e) {
            return null;
        }

        $cursor = $now->copy()->timezone($tz);

        // buscamos la próxima ejecución válida (saltando horas bloqueadas si aplica)
        for ($i = 0; $i < 48; $i++) {
            $dt = $expr->getNextRunDate($cursor->toDateTimeImmutable(), 0, true, $tz);
            $next = Carbon::instance(\DateTime::createFromInterface($dt))->timezone($tz);

            if (!in_array((int)$next->hour, $skipHours, true)) {
                return $next;
            }

            // si cae en una hora bloqueada (ej. 19:00), avanzamos 1 minuto y recalculamos
            $cursor = $next->copy()->addMinute();
        }

        return null;
    }

    private function lastActivity(?string $path, string $tz): ?Carbon
    {
        if (!$path || !is_file($path)) return null;

        $ts = @filemtime($path);
        if (!$ts) return null;

        return Carbon::createFromTimestamp($ts, $tz);
    }

    private function humanCountdown(Carbon $from, Carbon $to): string
    {
        $sec = $from->diffInSeconds($to, false);
        if ($sec <= 0) return 'Ahora';

        return CarbonInterval::seconds($sec)
            ->cascade()
            ->forHumans(['short' => true, 'parts' => 2]);
    }

    private function urgency(Carbon $now, Carbon $next): string
    {
        $mins = $now->diffInMinutes($next, false);
        if ($mins <= 5) return 'now';
        if ($mins <= 60) return 'soon';
        return 'muted';
    }
}
