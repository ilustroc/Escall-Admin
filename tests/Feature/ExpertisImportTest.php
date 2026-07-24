<?php

namespace Tests\Feature;

use App\Models\GestionExpertis;
use App\Models\ImportacionExpertis;
use App\Models\PagoExpertis;
use App\Models\User;
use Database\Seeders\TipificacionesExpertisSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ExpertisImportTest extends TestCase
{
    private array $temporales = [];

    private User $usuario;

    private const ENCABEZADOS_GESTIONES = [
        'Canal Gestión',
        'Canal Asignación',
        'DNI',
        'Nombre Cliente',
        'Cartera',
        'Asesor',
        'Equipo',
        'Teléfono',
        'Fecha Llamada',
        'Campaña',
        'Hora',
        'Nivel 1',
        'Nivel 2',
        'Fecha Compromiso',
        'Monto',
        'Observación',
        'Medio Gestión',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(TipificacionesExpertisSeeder::class);
        $this->usuario = User::factory()->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }

        parent::tearDown();
    }

    public function test_usuario_no_autenticado_no_puede_cargar_archivos(): void
    {
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('01/07/2026'),
        ]);

        $respuesta = $this->post('/expertis/importaciones/gestiones/preview', [
            'archivo' => $this->uploadedCopy($archivo),
        ]);

        $respuesta->assertRedirect(route('login'));
        $this->assertDatabaseCount('importaciones_expertis', 0);
    }

    public function test_archivo_sin_columnas_obligatorias_es_rechazado_antes_de_importar(): void
    {
        $archivo = $this->crearXlsx(['DNI', 'Cartera'], [['09487554', 'OH']]);

        $respuesta = $this->actingAs($this->usuario)
            ->postJson('/expertis/importaciones/gestiones/preview', [
                'archivo' => $this->uploadedCopy($archivo),
            ]);

        $respuesta->assertOk()
            ->assertJsonPath('token', null)
            ->assertJsonPath('columnas_faltantes', ['Fecha Llamada', 'Nivel 2']);
        $this->assertDatabaseCount('gestiones_expertis', 0);
    }

    public function test_archivo_acumulado_solo_agrega_el_dia_nuevo(): void
    {
        $archivo20 = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('19/07/2026', 'GESTIÓN 19', '09487554'),
            $this->filaGestion('20/07/2026', 'GESTIÓN 20', '09487555'),
        ]);
        $this->importarGestiones($archivo20);

        $archivo21 = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('19/07/2026', 'GESTIÓN 19', '09487554'),
            $this->filaGestion('20/07/2026', 'GESTIÓN 20', '09487555'),
            $this->filaGestion('21/07/2026', 'GESTIÓN 21', '09487556'),
        ]);
        $this->importarGestiones($archivo21);

        $this->assertDatabaseCount('gestiones_expertis', 3);
        $ultima = ImportacionExpertis::latest()->firstOrFail();
        $this->assertSame(1, $ultima->filas_insertadas);
        $this->assertSame(2, $ultima->filas_duplicadas);
    }

    public function test_mismo_archivo_completo_se_detecta_como_duplicado(): void
    {
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('21/07/2026'),
        ]);
        $this->importarGestiones($archivo);

        $segunda = $this->preview($archivo, 'gestiones');
        $segunda->assertOk()->assertJsonPath('archivo_duplicado.id', 1);
        $token = $segunda->json('token');
        $this->actingAs($this->usuario)->post('/expertis/importaciones/gestiones', [
            'preview_token' => $token,
        ])->assertRedirect();

        $this->assertDatabaseCount('importaciones_expertis', 1);
        $this->assertDatabaseCount('gestiones_expertis', 1);
    }

    public function test_dos_filas_iguales_en_el_mismo_archivo_no_se_duplican(): void
    {
        $fila = $this->filaGestion('21/07/2026');
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [$fila, $fila]);

        $this->importarGestiones($archivo);

        $this->assertDatabaseCount('gestiones_expertis', 1);
        $importacion = ImportacionExpertis::firstOrFail();
        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame(1, $importacion->filas_duplicadas);
    }

    public function test_fila_modificada_actualiza_campos_permitidos(): void
    {
        $primero = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('21/07/2026', 'NOTA CORTA', '09487554', 0),
        ]);
        $this->importarGestiones($primero);

        $segundo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion(
                '21/07/2026',
                'NOTA CORTA CON INFORMACIÓN ADICIONAL',
                '09487554',
                250,
            ),
        ]);
        $this->importarGestiones($segundo);

        $this->assertDatabaseCount('gestiones_expertis', 1);
        $gestion = GestionExpertis::firstOrFail();
        $this->assertSame(1, ImportacionExpertis::latest()->first()->filas_actualizadas);
        $this->assertSame('250.00', $gestion->monto);
        $this->assertSame('NOTA CORTA CON INFORMACIÓN ADICIONAL', $gestion->observacion);
    }

    public function test_fila_mas_completa_enriquece_asesor_sin_crear_duplicado(): void
    {
        $filaIncompleta = $this->filaGestion('21/07/2026');
        $filaIncompleta[5] = '';
        $this->importarGestiones($this->crearXlsx(
            self::ENCABEZADOS_GESTIONES,
            [$filaIncompleta],
        ));

        $filaCompleta = $this->filaGestion('21/07/2026');
        $filaCompleta[5] = 'ANA PEREZ';
        $this->importarGestiones($this->crearXlsx(
            self::ENCABEZADOS_GESTIONES,
            [$filaCompleta],
        ));

        $this->assertDatabaseCount('gestiones_expertis', 1);
        $this->assertSame('ANA PEREZ', GestionExpertis::firstOrFail()->asesor);
        $this->assertSame(1, ImportacionExpertis::latest()->firstOrFail()->filas_actualizadas);
    }

    public function test_pagos_duplicados_exactos_se_ignoran(): void
    {
        $headers = ['FECHA', 'CUENTA', 'MONTO', 'EJECUTIVO', 'TIPO DE ACUERDO', 'RECAUDO'];
        $fila = ['21/07/2026', '09487554-OH', 'S/ 200.00', 'Ana', 'Cuota', 'Banco'];
        $archivo = $this->crearXlsx($headers, [$fila, $fila]);

        $preview = $this->preview($archivo, 'pagos');
        $token = $preview->json('token');
        $this->actingAs($this->usuario)->post('/expertis/importaciones/pagos', [
            'preview_token' => $token,
        ])->assertRedirect();

        $this->assertDatabaseCount('pagos_expertis', 1);
        $this->assertSame('9487554-OH', PagoExpertis::first()->cuenta_normalizada);
        $this->assertSame(1, ImportacionExpertis::first()->filas_duplicadas);
    }

    private function importarGestiones(string $archivo): void
    {
        $preview = $this->preview($archivo, 'gestiones');
        $preview->assertOk()->assertJsonPath('columnas_faltantes', []);

        $this->actingAs($this->usuario)->post('/expertis/importaciones/gestiones', [
            'preview_token' => $preview->json('token'),
        ])->assertRedirect();
    }

    private function preview(string $archivo, string $tipo)
    {
        return $this->actingAs($this->usuario)
            ->postJson("/expertis/importaciones/{$tipo}/preview", [
                'archivo' => $this->uploadedCopy($archivo),
            ]);
    }

    private function filaGestion(
        string $fecha,
        string $observacion = 'CLIENTE CONTACTADO',
        string $dni = '09487554',
        float $monto = 0,
    ): array {
        return [
            'CALL',
            'CARTERA',
            $dni,
            'CLIENTE FICTICIO',
            'OH',
            'ANA PEREZ',
            'EQUIPO 1',
            '999888777',
            $fecha,
            'JULIO',
            '10:15:00',
            'CONTACTO',
            'PPC',
            '25/07/2026',
            $monto,
            $observacion,
            'LLAMADA',
        ];
    }

    private function crearXlsx(array $encabezados, array $filas): string
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'expertis_'.Str::uuid().'.xlsx';
        $this->temporales[] = $ruta;

        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues($encabezados));
        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }
        $writer->close();

        return $ruta;
    }

    private function uploadedCopy(string $origen): UploadedFile
    {
        $copia = sys_get_temp_dir().DIRECTORY_SEPARATOR.'upload_'.Str::uuid().'.xlsx';
        copy($origen, $copia);
        $this->temporales[] = $copia;

        return new UploadedFile(
            $copia,
            'expertis.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
