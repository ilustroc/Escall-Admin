<?php

namespace Tests\Feature;

use App\Models\TipificacionExpertis;
use App\Models\User;
use App\Queries\Expertis\ExpertisReportQuery;
use Database\Seeders\TipificacionesExpertisSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpertisBusinessRulesTest extends TestCase
{
    private ExpertisReportQuery $reportes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipificacionesExpertisSeeder::class);
        $this->reportes = app(ExpertisReportQuery::class);
    }

    public function test_menor_peso_recibe_unico_uno(): void
    {
        $this->crearGestion('1234567-OH', 'RPP', '2026-07-20 12:00:00');
        $ganadora = $this->crearGestion('1234567-OH', 'PPC', '2026-07-19 12:00:00');

        $resultado = $this->reportes->gestiones(['codigo' => '1234567-OH', 'unico' => '1'], false)->get();

        $this->assertCount(1, $resultado);
        $this->assertSame($ganadora, $resultado->first()->id);
        $this->assertSame(1, (int) $resultado->first()->unico);
    }

    public function test_mismo_peso_desempata_por_fecha_hora_y_solo_hay_un_unico(): void
    {
        $primera = $this->crearGestion('7654321-TEC', 'PPC', '2026-07-20 09:00:00');
        $ultima = $this->crearGestion('7654321-TEC', 'PPC', '2026-07-20 11:00:00');

        $filas = $this->reportes->gestiones(['codigo' => '7654321-TEC'], false)->get();

        $this->assertSame(1, $filas->where('unico', 1)->count());
        $this->assertSame($ultima, $filas->firstWhere('unico', 1)->id);
        $this->assertNotSame($primera, $ultima);
    }

    public function test_pago_anterior_no_marca_pago_y_pago_posterior_si(): void
    {
        $gestion = $this->crearGestion('9487554-OH', 'PPC', '2026-07-01 10:00:00');
        $this->crearPago('9487554-OH', '2026-05-18', 100);

        $antes = $this->reportes->gestiones(['codigo' => '9487554-OH'], false)
            ->where('r.id', $gestion)
            ->first();
        $this->assertSame('NO PAGO', $antes->estado);
        $this->assertSame(0.0, (float) $antes->monto_pagado);

        $this->crearPago('9487554-OH', '2026-07-15', 250);
        $despues = $this->reportes->gestiones(['codigo' => '9487554-OH'], false)
            ->where('r.id', $gestion)
            ->first();

        $this->assertSame('PAGO', $despues->estado);
        $this->assertSame(250.0, (float) $despues->monto_pagado);
        $this->assertSame('2026-07-15', $despues->fecha_pagada);
    }

    public function test_ppc_sin_pago_real_permanece_no_pago(): void
    {
        $gestion = $this->crearGestion('1111111-OH', 'PPC', '2026-07-01 10:00:00');

        $fila = $this->reportes->gestiones([], false)->where('r.id', $gestion)->first();

        $this->assertSame('NO PAGO', $fila->estado);
    }

    public function test_cambiar_peso_modifica_inmediatamente_el_unico(): void
    {
        $ppc = $this->crearGestion('2222222-OH', 'PPC', '2026-07-01 10:00:00');
        $ppm = $this->crearGestion('2222222-OH', 'PPM', '2026-07-02 10:00:00');

        $inicial = $this->reportes->gestiones(['codigo' => '2222222-OH', 'unico' => '1'], false)->first();
        $this->assertSame($ppc, $inicial->id);

        TipificacionExpertis::where('tipificacion', 'PPC')->update(['peso' => 20]);
        TipificacionExpertis::where('tipificacion', 'PPM')->update(['peso' => 1]);

        $actualizada = $this->reportes->gestiones(['codigo' => '2222222-OH', 'unico' => '1'], false)->first();
        $this->assertSame($ppm, $actualizada->id);
    }

    public function test_export_reutiliza_exactamente_la_consulta_filtrada(): void
    {
        $this->crearGestion('3333333-A', 'PPC', '2026-07-01 10:00:00', ['cartera' => 'A']);
        $esperada = $this->crearGestion('4444444-B', 'PPM', '2026-07-02 10:00:00', ['cartera' => 'B']);

        $filtros = ['cartera' => 'B', 'fecha_inicio' => '2026-07-02', 'fecha_fin' => '2026-07-02'];
        $pantalla = $this->reportes->gestiones($filtros)->pluck('id')->all();
        $export = (new \App\Exports\ExpertisGestionesExport($filtros))->query()->pluck('id')->all();

        $this->assertSame([$esperada], $pantalla);
        $this->assertSame($pantalla, $export);
    }

    public function test_tabla_gestiones_abre_sin_parametro_per_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/expertis/gestiones')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expertis/Gestiones/Index')
                ->where('gestiones.per_page', 25));
    }

    public function test_tabla_pagos_abre_sin_parametro_per_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/expertis/pagos')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expertis/Pagos/Index')
                ->where('pagos.per_page', 25));
    }

    private function crearGestion(
        string $codigoNormalizado,
        string $nivel2,
        string $fechaHora,
        array $extra = [],
    ): int {
        $tipificacion = TipificacionExpertis::where('tipificacion', $nivel2)->firstOrFail();
        [$documento, $carteraCodigo] = array_pad(explode('-', $codigoNormalizado, 2), 2, 'OH');
        $cartera = $extra['cartera'] ?? $carteraCodigo;
        $ahora = now()->toDateTimeString();

        return DB::table('gestiones_expertis')->insertGetId(array_merge([
            'codigo' => str_pad($documento, 8, '0', STR_PAD_LEFT).'-'.$cartera,
            'codigo_normalizado' => $codigoNormalizado,
            'dni' => str_pad($documento, 8, '0', STR_PAD_LEFT),
            'cartera' => $cartera,
            'fecha_llamada' => substr($fechaHora, 0, 10),
            'hora' => substr($fechaHora, 11),
            'fecha_hora' => $fechaHora,
            'nivel_2' => $nivel2,
            'tipificacion_expertis_id' => $tipificacion->id,
            'monto' => 0,
            'hash_fila' => hash('sha256', $codigoNormalizado.$nivel2.$fechaHora.random_int(1, 999999)),
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $extra));
    }

    private function crearPago(string $cuentaNormalizada, string $fecha, float $monto): void
    {
        DB::table('pagos_expertis')->insert([
            'fecha' => $fecha,
            'cuenta' => $cuentaNormalizada,
            'cuenta_normalizada' => $cuentaNormalizada,
            'monto' => $monto,
            'hash_fila' => hash('sha256', $cuentaNormalizada.$fecha.$monto.random_int(1, 999999)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
