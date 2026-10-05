<?php

namespace Tests\Feature;

use App\Exports\ExpertisGestionesExport;
use App\Models\TipificacionExpertis;
use App\Queries\Expertis\ExpertisReportQuery;
use Database\Seeders\TipificacionesExpertisSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpertisLlamadasReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipificacionesExpertisSeeder::class);
    }

    public function test_report_unifies_manual_and_dialer_with_the_expected_medium_and_assignment_enrichment(): void
    {
        $now = now();
        DB::table('asignaciones_expertis')->insert([
            'periodo' => '202609',
            'empresa' => 'ESCALL',
            'documento' => '43115307',
            'titular' => 'CLIENTE ENRIQUECIDO',
            'codigo' => '43115307-OH',
            'codigo_normalizado' => '43115307-OH',
            'tipo_cartera' => 'OH',
            'hash_fila' => hash('sha256', 'assignment'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $tipificacion = TipificacionExpertis::where('tipificacion', 'PPC')->firstOrFail();
        DB::table('gestiones_expertis')->insert([
            'codigo' => '43115307-OH',
            'codigo_normalizado' => '43115307-OH',
            'dni' => '43115307',
            'cartera' => 'OH',
            'fecha_llamada' => '2026-09-02',
            'hora' => '08:05:00',
            'fecha_hora' => '2026-09-02 08:05:00',
            'nivel_2' => 'PPC',
            'tipificacion_expertis_id' => $tipificacion->id,
            'monto' => 0,
            'hash_fila' => hash('sha256', 'manual'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('llamadas')->insert([
            'fecha_gestion' => '2026-09-02 08:37:01',
            'documento' => '43115307',
            'telefono' => '966704305',
            'cartera' => 'MULTI',
            'resultado' => 'BUZON',
            'tipo_gestion' => 'NO CONTACTO',
            'observacion' => 'BUZON',
            'usuario' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows = app(ExpertisReportQuery::class)->gestiones([
            'fecha_inicio' => '2026-09-02',
            'fecha_fin' => '2026-09-02',
        ], false)->get()->keyBy('origen');

        $this->assertCount(2, $rows);
        $this->assertSame('MANUAL', $rows['MANUAL']->medio_gestion);
        $this->assertSame('DISCADOR', $rows['DISCADOR']->medio_gestion);
        $this->assertSame('43115307', $rows['DISCADOR']->dni);
        $this->assertSame('43115307-OH', $rows['DISCADOR']->codigo_normalizado);
        $this->assertSame('CLIENTE ENRIQUECIDO', $rows['DISCADOR']->nombre_cliente);
        $this->assertSame('MARCADOR', $rows['DISCADOR']->asesor);
        $this->assertSame('KATHERINE HUAMAN', $rows['DISCADOR']->equipo);
        $this->assertNull($rows['DISCADOR']->peso);
        $this->assertSame(500, (new ExpertisGestionesExport([]))->chunkSize());
    }

    public function test_report_uses_an_exclusive_end_date_for_dialer_rows(): void
    {
        $now = now();
        foreach (['2026-09-02 00:00:00', '2026-09-02 23:59:59', '2026-09-03 00:00:00'] as $index => $fecha) {
            DB::table('llamadas')->insert([
                'fecha_gestion' => $fecha,
                'documento' => 'doc-'.$index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $rows = app(ExpertisReportQuery::class)->gestiones([
            'fecha_inicio' => '2026-09-02',
            'fecha_fin' => '2026-09-02',
            'medio_gestion' => 'DISCADOR',
        ], false)->get();

        $this->assertCount(2, $rows);
        $this->assertSame(['doc-0', 'doc-1'], $rows->pluck('dni')->sort()->values()->all());
    }
}
