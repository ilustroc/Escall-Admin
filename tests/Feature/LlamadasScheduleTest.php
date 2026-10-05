<?php

namespace Tests\Feature;

use App\Console\Kernel;
use App\Queries\Reportes\ReporteImpulseQuery;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LlamadasScheduleTest extends TestCase
{
    public function test_scheduler_uses_non_overlapping_llamadas_job_and_the_19_hour_ordered_flow(): void
    {
        $schedule = new Schedule(app());
        $method = new \ReflectionMethod(Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(app(Kernel::class), $schedule);

        $hourly = collect($schedule->events())->first(fn ($event) => str_contains($event->command, 'llamadas:sync-sp-hourly'));
        $daily = collect($schedule->events())->first(fn ($event) => str_contains($event->command, 'gestiones:sync-daily-report'));

        $this->assertNotNull($hourly);
        $this->assertTrue($hourly->withoutOverlapping);
        $this->assertSame('America/Lima', $hourly->timezone);
        $this->assertNotNull($daily);
        $this->assertTrue($daily->withoutOverlapping);

        $flow = file_get_contents(app_path('Console/Commands/SyncDailyGestionesReport.php'));
        $this->assertLessThan(
            strpos($flow, 'gestiones:sync-sp-hourly'),
            strpos($flow, 'llamadas:sync-sp-hourly'),
        );
        $this->assertStringContainsString("'--send-mail' => true", $flow);
    }

    public function test_daily_mail_query_contains_manual_and_dialer_calls_after_sync(): void
    {
        $now = now();
        DB::table('data')->insert([
            'codigo' => '43115307-IMPULSE',
            'dni' => '43115307',
            'titular' => 'CLIENTE',
            'cartera' => 'IMPULSE',
            'entidad' => 'OH',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('gestiones')->insert([
            'fecha_gestion' => '2026-09-02 08:00:00',
            'dni' => '43115307',
            'status' => 'CONTACTO',
            'tipificacion' => 'PPC',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('llamadas')->insert([
            'fecha_gestion' => '2026-09-02 09:00:00',
            'documento' => '43115307',
            'resultado' => 'BUZON',
            'tipo_gestion' => 'NO CONTACTO',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows = app(ReporteImpulseQuery::class)->builder('2026-09-02', '2026-09-02', 2)->get();

        $this->assertSame(['DISCADOR', 'MANUAL'], $rows->pluck('procedencia_llamada')->sort()->values()->all());
    }
}
