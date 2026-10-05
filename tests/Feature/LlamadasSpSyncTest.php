<?php

namespace Tests\Feature;

use App\Services\Cargas\LlamadasSpService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LlamadasSpSyncTest extends TestCase
{
    public function test_maps_the_fifteen_columns_and_preserves_multiline_accounts(): void
    {
        $service = $this->service([$this->row()]);

        $stats = $service->sync('2026-09-02 08:00:00', '2026-09-02 09:00:00');

        $this->assertSame(1, $stats['obtenidos']);
        $this->assertSame(1, $stats['insertados']);
        $this->assertSame(0, $stats['errores']);
        $this->assertDatabaseHas('llamadas', [
            'fecha_gestion' => '2026-09-02 08:37:01',
            'fecha_inicio' => '2026-09-02 08:37:01',
            'fecha_final' => '2026-09-02 08:37:01',
            'documento' => '43115307',
            'telefono' => '966704305',
            'cliente' => 'ABRAHAM FLORES ESPINOZA',
            'cartera' => 'MULTI_AGOSTO_2026',
            'campana' => 'MULTI_CD+AGOSTO_01092026',
            'codigo_estado' => '5',
            'resultado' => 'BUZON',
            'tipo_gestion' => 'NO CONTACTO',
            'observacion' => 'BUZON',
            'usuario' => 'MARCADOR',
        ]);
        $this->assertSame("00110831559600535094\n00999999999999999999", DB::table('llamadas')->value('cuenta'));
    }

    public function test_interval_is_semiopen_idempotent_and_preserves_rows_outside_it(): void
    {
        DB::table('llamadas')->insert($this->localRow('2026-09-02 07:59:59', 'outside-before'));
        DB::table('llamadas')->insert($this->localRow('2026-09-02 09:00:00', 'outside-after'));
        DB::table('llamadas')->insert($this->localRow('2026-09-02 08:10:00', 'stale-inside'));

        $rows = [
            $this->row(['fecha_gestion' => '2026-09-02 08:00:00', 'documento' => 'inicio']),
            $this->row(['fecha_gestion' => '2026-09-02 08:59:59', 'documento' => 'fin-menos-uno']),
            $this->row(['fecha_gestion' => '2026-09-02 09:00:00', 'documento' => 'fin-exclusivo']),
        ];
        $service = $this->service($rows);

        $service->sync('2026-09-02 08:00:00', '2026-09-02 09:00:00');
        $service->sync('2026-09-02 08:00:00', '2026-09-02 09:00:00');

        $this->assertSame(4, DB::table('llamadas')->count());
        $this->assertSame(1, DB::table('llamadas')->where('documento', 'inicio')->count());
        $this->assertSame(1, DB::table('llamadas')->where('documento', 'fin-menos-uno')->count());
        $this->assertSame(0, DB::table('llamadas')->where('documento', 'fin-exclusivo')->count());
        $this->assertSame(1, DB::table('llamadas')->where('documento', 'outside-before')->count());
        $this->assertSame(1, DB::table('llamadas')->where('documento', 'outside-after')->count());
    }

    public function test_inserts_in_chunks_of_two_thousand(): void
    {
        $source = function (): iterable {
            for ($i = 0; $i < 2001; $i++) {
                yield $this->row([
                    'fecha_gestion' => '2026-09-02 08:'.str_pad((string) intdiv($i, 60), 2, '0', STR_PAD_LEFT).':'.str_pad((string) ($i % 60), 2, '0', STR_PAD_LEFT),
                    'documento' => (string) $i,
                ]);
            }
        };
        $service = new LlamadasSpService(fn () => $source());

        $stats = $service->sync('2026-09-02 08:00:00', '2026-09-02 09:00:00');

        $this->assertSame(2001, $stats['insertados']);
        $this->assertDatabaseCount('llamadas', 2001);
    }

    public function test_dry_run_reads_and_maps_without_modifying_local_data(): void
    {
        DB::table('llamadas')->insert($this->localRow('2026-09-02 08:15:00', 'existing'));
        $service = $this->service([$this->row()]);

        $stats = $service->sync('2026-09-02 08:00:00', '2026-09-02 09:00:00', true);

        $this->assertSame(1, $stats['obtenidos']);
        $this->assertSame(0, $stats['insertados']);
        $this->assertCount(1, $stats['muestra']);
        $this->assertDatabaseCount('llamadas', 1);
        $this->assertDatabaseHas('llamadas', ['documento' => 'existing']);
    }

    public function test_command_splits_a_backfill_by_day_and_dry_run_does_not_write(): void
    {
        $ranges = [];
        app()->instance(LlamadasSpService::class, new LlamadasSpService(
            function (string $from, string $to) use (&$ranges): iterable {
                $ranges[] = [$from, $to];
                yield $this->row(['fecha_gestion' => $from]);
            },
        ));

        $this->artisan('llamadas:sync-sp-hourly', [
            '--from' => '2026-09-01 00:00:00',
            '--to' => '2026-09-03 00:00:00',
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame([
            ['2026-09-01 00:00:00', '2026-09-02 00:00:00'],
            ['2026-09-02 00:00:00', '2026-09-03 00:00:00'],
        ], $ranges);
        $this->assertDatabaseCount('llamadas', 0);
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function service(array $rows): LlamadasSpService
    {
        return new LlamadasSpService(fn () => $rows);
    }

    /** @param array<string, mixed> $replace */
    private function row(array $replace = []): array
    {
        return array_merge([
            'Fecha Gestión' => '2026-09-02 08:37:01',
            'fecha_inicio' => '2026-09-02 08:37:01',
            'fecha_final' => '2026-09-02 08:37:01',
            'documento' => '43115307',
            'cuenta' => "00110831559600535094\n00999999999999999999",
            'telefono' => '966704305',
            'variable' => '',
            'cliente' => 'ABRAHAM FLORES ESPINOZA',
            'cartera' => 'MULTI_AGOSTO_2026',
            'campana' => 'MULTI_CD+AGOSTO_01092026',
            'codigo_estado' => '5',
            'resultado' => 'BUZON',
            'tipo_gestion' => 'NO CONTACTO',
            'observacion' => 'BUZON',
            'usuario' => 'MARCADOR',
        ], $replace);
    }

    /** @return array<string, mixed> */
    private function localRow(string $fecha, string $documento): array
    {
        return [
            'fecha_gestion' => $fecha,
            'documento' => $documento,
        ];
    }
}
