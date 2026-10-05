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
            'codigo' => '43115307-SEPTIEMBRE',
            'codigo_normalizado' => '43115307-SEPTIEMBRE',
            'tipo_cartera' => 'OH',
            'hash_fila' => hash('sha256', 'assignment'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('asignaciones_expertis')->insert([
            'periodo' => '202607',
            'empresa' => 'ESCALL',
            'documento' => '43115307',
            'titular' => 'CLIENTE JULIO',
            'codigo' => '43115307-JULIO',
            'codigo_normalizado' => '43115307-JULIO',
            'tipo_cartera' => 'OH',
            'hash_fila' => hash('sha256', 'assignment-july'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('asignaciones_expertis')->insert([
            'periodo' => '202609',
            'empresa' => 'ESCALL',
            'documento' => '43115307',
            'titular' => 'CLIENTE SEPTIEMBRE RECIENTE',
            'codigo' => '43115307-OH',
            'codigo_normalizado' => '43115307-OH',
            'tipo_cartera' => 'OH',
            'hash_fila' => hash('sha256', 'assignment-september-newer'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('asignaciones_expertis')->insert([
            'periodo' => '202607',
            'empresa' => 'ESCALL',
            'documento' => '99999999',
            'titular' => 'CLIENTE SOLO JULIO',
            'codigo' => '99999999-JULIO',
            'codigo_normalizado' => '99999999-JULIO',
            'tipo_cartera' => 'OH',
            'hash_fila' => hash('sha256', 'assignment-only-july'),
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
        ]);
        DB::table('llamadas')->insert([
            'fecha_gestion' => '2026-09-02 09:00:00',
            'documento' => '99999999',
            'cliente' => 'CLIENTE DEL DISCADOR',
            'cartera' => 'SIN ASIGNACION',
            'resultado' => 'NO CONTESTA',
            'tipo_gestion' => 'NO CONTACTO',
        ]);

        $reportes = app(ExpertisReportQuery::class);
        $filtros = [
            'fecha_inicio' => '2026-09-02',
            'fecha_fin' => '2026-09-02',
        ];
        $rows = $reportes->gestiones($filtros, false)->get();
        $manual = $rows->firstWhere('origen', 'MANUAL');
        $discadorAsignado = $rows->firstWhere('dni', '43115307');
        $discadorSinAsignacion = $rows->firstWhere('dni', '99999999');

        $this->assertCount(3, $rows);
        $this->assertSame('MANUAL', $manual->medio_gestion);
        $this->assertSame('DISCADOR', $discadorAsignado->medio_gestion);
        $this->assertSame('43115307', $discadorAsignado->dni);
        $this->assertSame('43115307-SEPTIEMBRE', $discadorAsignado->codigo_normalizado);
        $this->assertSame('CLIENTE SEPTIEMBRE RECIENTE', $discadorAsignado->nombre_cliente);
        $this->assertSame('MARCADOR', $discadorAsignado->asesor);
        $this->assertSame('KATHERINE HUAMAN', $discadorAsignado->equipo);
        $this->assertNull($discadorAsignado->peso);
        $this->assertSame('99999999', $discadorSinAsignacion->codigo_normalizado);
        $this->assertSame('CLIENTE DEL DISCADOR', $discadorSinAsignacion->nombre_cliente);
        $this->assertSame('DISCADOR', $discadorSinAsignacion->medio_gestion);
        $this->assertCount(1, $reportes->gestiones(array_merge($filtros, ['medio_gestion' => 'MANUAL']), false)->get());
        $this->assertCount(2, $reportes->gestiones(array_merge($filtros, ['medio_gestion' => 'DISCADOR']), false)->get());
        $this->assertSame(3, $reportes->resumenGestiones($filtros)['totales']['total_gestiones']);
        $this->assertSame(500, (new ExpertisGestionesExport([]))->chunkSize());
    }

    public function test_report_uses_an_exclusive_end_date_for_dialer_rows(): void
    {
        foreach (['2026-09-02 00:00:00', '2026-09-02 23:59:59', '2026-09-03 00:00:00'] as $index => $fecha) {
            DB::table('llamadas')->insert([
                'fecha_gestion' => $fecha,
                'documento' => 'doc-'.$index,
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
