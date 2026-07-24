<?php

namespace Tests\Feature;

use App\Models\Data;
use App\Models\Gestion;
use App\Models\Pago;
use App\Models\User;
use App\Services\Cargas\GestionesSpService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegacyModulesFunctionalTest extends TestCase
{
    public function test_login_success_failure_and_logout(): void
    {
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'remember' => true,
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_sp_preview_import_and_date_validation(): void
    {
        $service = $this->mock(GestionesSpService::class);
        $service->shouldReceive('preview')
            ->once()
            ->with('2026-07-01', '2026-07-02')
            ->andReturn(['rows' => [['dni' => '12345678']], 'total' => 1, 'limit' => 100]);
        $service->shouldReceive('import')
            ->once()
            ->with('2026-07-01', '2026-07-02')
            ->andReturn(1);

        $this->actingAs($this->user())
            ->get('/cargas/sp/preview?fi=2026-07-01&ff=2026-07-02')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cargas/GestionesSp/Index')
                ->where('preview.total', 1));

        $this->post('/cargas/sp/import', ['fi' => '2026-07-01', 'ff' => '2026-07-02'])
            ->assertRedirect('/cargas/sp')
            ->assertSessionHas('success');

        $this->get('/cargas/sp/preview?fi=2026-07-03&ff=2026-07-02')
            ->assertSessionHasErrors('ff');
    }

    public function test_csv_data_load_and_template_download(): void
    {
        $csv = implode("\n", [
            'CODIGO,DNI,TITULAR,CARTERA,DEUDA_CAPITAL',
            'CTA-001,00123456,Cliente Demo,IMPULSE,1500.50',
        ]);

        $this->actingAs($this->user())
            ->post('/cargas/data/import-csv', [
                'csv' => UploadedFile::fake()->createWithContent('data.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('data', ['codigo' => 'CTA-001', 'dni' => '00123456']);
        $this->get('/cargas/data/templateCsv')
            ->assertOk()
            ->assertDownload('plantilla_data.csv');
    }

    public function test_payment_lookup_and_registration(): void
    {
        Data::query()->create([
            'codigo' => 'CTA-100',
            'dni' => '00001234',
            'titular' => 'Cliente Pago',
            'cartera' => 'KP INVEST',
            'deuda_capital' => 25000,
        ]);

        $this->actingAs($this->user())
            ->getJson('/cargas/pagos/lookup?codigo=CTA-100')
            ->assertOk()
            ->assertJsonPath('data.dni', '00001234')
            ->assertJsonPath('data.rango', '2.[20K - 50K]');

        $this->post('/cargas/pagos', [
            'codigo' => 'CTA-100',
            'fecha' => '2026-07-23',
            'monto' => 350.75,
            'operacion' => 'PAGO PARCIAL',
            'dni' => '00001234',
            'capital' => 25000,
        ])->assertRedirect('/cargas/pagos');

        $this->assertDatabaseHas('pagos', [
            'codigo' => 'CTA-100',
            'monto' => 350.75,
            'rango' => '2.[20K - 50K]',
        ]);
    }

    public function test_lists_edit_delete_filters_and_export(): void
    {
        $payment = Pago::query()->create([
            'codigo' => 'CTA-200',
            'fecha' => '2026-07-20',
            'monto' => 100,
            'operacion' => 'CUOTA',
            'dni' => '99999999',
            'asesor' => 'Ana',
        ]);

        $this->actingAs($this->user())
            ->get('/listas?tab=pagos&dni=9999&per_page=25&direction=desc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listas/Index')
                ->has('registros.data', 1));

        $this->put('/listas/update', [
            'id' => $payment->id,
            'type' => 'pago',
            'monto' => 125.50,
        ])->assertRedirect();
        $this->assertDatabaseHas('pagos', ['id' => $payment->id, 'monto' => 125.50]);

        $this->get('/listas/export?tab=pagos&per_page=25&direction=desc')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->delete('/listas/delete', ['id' => $payment->id, 'type' => 'pago'])
            ->assertRedirect();
        $this->assertDatabaseMissing('pagos', ['id' => $payment->id]);
    }

    public function test_table_and_report_filters_return_inertia(): void
    {
        Data::query()->create([
            'codigo' => 'CTA-300',
            'dni' => '12345678',
            'titular' => 'Cliente Reporte',
            'cartera' => 'IMPULSE',
            'entidad' => 'OTRA',
            'deuda_capital' => 800,
        ]);
        Gestion::query()->create([
            'fecha_gestion' => '2026-07-23 10:00:00',
            'dni' => '12345678',
            'tipificacion' => 'CONTACTO',
            'status' => 'CONTACTO',
            'nombre' => 'Asesor Uno',
        ]);

        $this->actingAs($this->user())
            ->get('/tablas?tab=gestiones&mes=2026-07&week=2026-W30&search=1234&per_page=25&direction=desc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tablas/Index')
                ->where('resultado.total_general', 1));

        $this->get('/reportes/impulse?fi=2026-07-23&ff=2026-07-23&equipo=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Impulse')
                ->where('total', 1));

        $this->get('/reportes/kp-invest?fi=2026-07-23&ff=2026-07-23')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reportes/KpInvest'));

        $this->get('/reportes/carteras?mes=2026-07&tag=JULIO&lote=1809')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reportes/Carteras'));
    }

    public function test_existing_report_exports_remain_downloadable(): void
    {
        $this->actingAs($this->user());

        $this->get('/reportes/impulse/export?fi=2026-07-23&ff=2026-07-23&equipo=2')
            ->assertOk()
            ->assertDownload('Gestiones Cartera Propia - 2 Escall 20260723.xlsx');

        $this->get('/reportes/kp-invest/export?fi=2026-07-23&ff=2026-07-23')
            ->assertOk()
            ->assertDownload('Reporte KP INVEST 20260723.xlsx');

        $this->get('/reportes/carteras/export-tec?mes=2026-07&tag=JULIO&lote=1809')
            ->assertOk()
            ->assertDownload('FRMT_RG AGENCIAS EXTERNAS 1809.xlsx');
    }

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Admin Legacy',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('secret123'),
        ]);
    }
}
