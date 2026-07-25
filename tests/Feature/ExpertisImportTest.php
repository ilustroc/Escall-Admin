<?php

namespace Tests\Feature;

use App\Models\GestionExpertis;
use App\Models\ImportacionExpertis;
use App\Models\PagoExpertis;
use App\Models\User;
use App\Services\Expertis\ExpertisSpreadsheetService;
use Database\Seeders\TipificacionesExpertisSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as PhpSpreadsheetXlsxWriter;
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

    public function test_importa_el_formato_real_de_gestiones_con_fechas_y_null_textuales(): void
    {
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            [
                'EXPERTIS',
                'ESCALL',
                '47752785',
                'ANDERSON RODRIGUEZ MUÑOZ',
                'QAPAQ',
                'ANDERSON RODRIGUEZ',
                'LUIS',
                '980617080',
                '1/01/2025 00:00',
                'APLICATIVO2',
                '07:53:17',
                'CONTACTO EFECTIVO',
                'PPM',
                '31/01/2025 00:00',
                500,
                'Sustento de pago PPM - Liquidación',
                'MANUAL',
            ],
            [
                'JZG',
                'ESCALL',
                '05333190',
                'SHEYLA NOEMI RUT RAMIREZ PACHECO',
                'CREDINKA',
                'NOEMIRP',
                'SIN_EQUIPO',
                '923051553',
                '2/01/2025 00:00',
                'CAMPANA JZG',
                '12:17:00',
                'CONTACTO EFECTIVO',
                'TAT',
                '',
                0,
                'NULL',
                'DISCADOR',
            ],
        ]);

        $this->importarGestiones($archivo);

        $this->assertDatabaseCount('gestiones_expertis', 2);

        $qapaq = GestionExpertis::query()->where('dni', '47752785')->firstOrFail();
        $this->assertSame('EXPERTIS', $qapaq->canal_gestion);
        $this->assertSame('ESCALL', $qapaq->canal_asignacion);
        $this->assertSame('QAPAQ', $qapaq->cartera);
        $this->assertSame('2025-01-01', $qapaq->fecha_llamada->toDateString());
        $this->assertSame('07:53:17', $qapaq->hora);
        $this->assertSame('2025-01-31', $qapaq->fecha_compromiso->toDateString());
        $this->assertSame('500.00', $qapaq->monto);
        $this->assertSame('MANUAL', $qapaq->medio_gestion);

        $credinka = GestionExpertis::query()->where('dni', '05333190')->firstOrFail();
        $this->assertSame('2025-01-02', $credinka->fecha_llamada->toDateString());
        $this->assertSame('CAMPANA JZG', $credinka->campania);
        $this->assertSame('DISCADOR', $credinka->medio_gestion);
        $this->assertNull($credinka->observacion);
        $this->assertNull($credinka->fecha_compromiso);
    }

    public function test_vista_previa_serializa_fechas_y_horas_de_excel_como_texto(): void
    {
        $fila = $this->filaGestion('21/07/2026');
        $archivo = $this->crearXlsxConFechasExcel($fila);

        $preview = $this->preview($archivo, 'gestiones');
        $preview
            ->assertOk()
            ->assertJsonPath('preview.0.fecha_llamada', '2026-07-06')
            ->assertJsonPath('preview.0.hora', '18:30:40')
            ->assertJsonPath('preview.0.fecha_compromiso', '2026-07-09');

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/gestiones', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        $gestion = GestionExpertis::firstOrFail();
        $this->assertSame('2026-07-06', $gestion->fecha_llamada->toDateString());
        $this->assertSame('18:30:40', $gestion->hora);
        $this->assertSame('2026-07-09', $gestion->fecha_compromiso->toDateString());
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

    public function test_importacion_fallida_con_el_mismo_hash_se_puede_reintentar(): void
    {
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('21/07/2026'),
        ]);
        $fallida = ImportacionExpertis::create([
            'user_id' => $this->usuario->id,
            'tipo' => ImportacionExpertis::TIPO_GESTIONES,
            'nombre_original' => 'intento-fallido.xlsx',
            'nombre_guardado' => '',
            'ruta_archivo' => '',
            'hash_archivo' => hash_file('sha256', $archivo),
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'mensaje_error' => 'No se pudo guardar el archivo.',
            'iniciado_at' => now()->subMinute(),
            'finalizado_at' => now()->subMinute(),
        ]);

        $preview = $this->preview($archivo, 'gestiones');
        $preview
            ->assertOk()
            ->assertJsonPath('archivo_duplicado', null)
            ->assertJsonPath('columnas_faltantes', []);

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/gestiones', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        $fallida->refresh();
        $this->assertSame(ImportacionExpertis::ESTADO_COMPLETADO, $fallida->estado);
        $this->assertSame(1, $fallida->total_filas);
        $this->assertSame(1, $fallida->filas_insertadas);
        $this->assertNull($fallida->mensaje_error);
        $this->assertNotSame('', $fallida->ruta_archivo);
        Storage::disk('local')->assertExists($fallida->ruta_archivo);
        $this->assertDatabaseCount('importaciones_expertis', 1);
        $this->assertDatabaseCount('gestiones_expertis', 1);
    }

    public function test_reintento_conserva_y_contabiliza_filas_insertadas_antes_del_fallo(): void
    {
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('21/07/2026'),
        ]);
        $this->importarGestiones($archivo);

        $importacion = ImportacionExpertis::firstOrFail();
        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'total_filas' => 0,
            'filas_insertadas' => 0,
            'filas_actualizadas' => 0,
            'filas_duplicadas' => 0,
            'filas_error' => 0,
            'finalizado_at' => now(),
            'mensaje_error' => 'La ejecuciÃ³n anterior fue interrumpida.',
        ]);

        $preview = $this->preview($archivo, 'gestiones');
        $preview
            ->assertOk()
            ->assertJsonPath('archivo_duplicado', null);

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/gestiones', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        $importacion->refresh();
        $this->assertSame(ImportacionExpertis::ESTADO_COMPLETADO, $importacion->estado);
        $this->assertSame(1, $importacion->total_filas);
        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame(0, $importacion->filas_duplicadas);
        $this->assertDatabaseCount('importaciones_expertis', 1);
        $this->assertDatabaseCount('gestiones_expertis', 1);
    }

    public function test_archivo_original_importado_se_puede_descargar(): void
    {
        $archivo = $this->crearXlsx(self::ENCABEZADOS_GESTIONES, [
            $this->filaGestion('21/07/2026'),
        ]);
        $this->importarGestiones($archivo);

        $importacion = ImportacionExpertis::firstOrFail();
        $respuesta = $this->actingAs($this->usuario)
            ->get(route('expertis.importaciones.archivo', $importacion));

        $respuesta
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
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

    public function test_importa_pagos_con_fecha_excel_en_la_primera_columna(): void
    {
        $archivo = $this->crearXlsxPagosConFechasExcel([
            ['2026-02-03', '47780017-LOS ANDES', 277, 'ROBERTO HUAMONTE', 'PPM', '-'],
            ['2026-02-03', '71742001-LOS ANDES', 1000, 'CLAUDIA ROMERO', 'PPC', '-'],
            ['2026-02-05', '01839200-CREDINKA', 200, 'ROBERTO HUAMONTE', 'PAR', '-'],
            ['2026-02-05', '40211919-OH', 1500, 'RUTH SANTAMARIA', 'PPC', '-'],
        ]);

        $preview = $this->preview($archivo, 'pagos');
        $preview
            ->assertOk()
            ->assertJsonPath('columnas_faltantes', [])
            ->assertJsonPath('preview.0.fecha', '2026-02-03')
            ->assertJsonPath('preview.0.cuenta', '47780017-LOS ANDES');

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/pagos', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('pagos_expertis', 4);
        $pago = PagoExpertis::query()
            ->where('cuenta', '01839200-CREDINKA')
            ->firstOrFail();
        $this->assertSame('2026-02-05', $pago->fecha->toDateString());
        $this->assertSame('01839200', $pago->dni);
        $this->assertSame('200.00', $pago->monto);
        $this->assertSame('-', $pago->recaudo);

        $importacion = ImportacionExpertis::firstOrFail();
        $this->assertSame(4, $importacion->total_filas);
        $this->assertSame(4, $importacion->filas_insertadas);
        $this->assertSame(0, $importacion->filas_error);
    }

    public function test_plantillas_expertis_son_xlsx_validos_para_el_importador(): void
    {
        $casos = [
            [
                '/expertis/importaciones/gestiones/plantilla',
                'plantilla_gestiones_expertis.xlsx',
                ExpertisSpreadsheetService::GESTIONES,
            ],
            [
                '/expertis/importaciones/pagos/plantilla',
                'plantilla_pagos_expertis.xlsx',
                ExpertisSpreadsheetService::PAGOS,
            ],
        ];

        foreach ($casos as [$url, $nombre, $tipo]) {
            $respuesta = $this->actingAs($this->usuario)->get($url);
            $respuesta
                ->assertOk()
                ->assertDownload($nombre);

            $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::uuid().'.xlsx';
            $this->temporales[] = $ruta;
            file_put_contents($ruta, $respuesta->streamedContent());

            $inspeccion = app(ExpertisSpreadsheetService::class)
                ->inspeccionar($ruta, $tipo);

            $this->assertSame([], $inspeccion['columnas_faltantes']);
            $this->assertCount(1, $inspeccion['preview']);
        }
    }

    public function test_pago_manual_se_registra_con_auditoria_y_no_se_duplica(): void
    {
        $datos = [
            'fecha' => '2025-01-31',
            'cuenta' => '47752785-qapaq',
            'monto' => 500,
            'ejecutivo' => 'Anderson Rodriguez',
            'tipo_acuerdo' => 'Cuota',
            'recaudo' => 'Aplicativo',
        ];

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/pagos/manual', $datos)
            ->assertRedirect('/expertis/importaciones/pagos?modo=manual')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('pagos_expertis', 1);
        $pago = PagoExpertis::firstOrFail();
        $this->assertSame('47752785-QAPAQ', $pago->cuenta);
        $this->assertSame('47752785-QAPAQ', $pago->cuenta_normalizada);
        $this->assertSame('47752785', $pago->dni);
        $this->assertSame('500.00', $pago->monto);
        $this->assertSame('ANDERSON RODRIGUEZ', $pago->ejecutivo);

        $auditoria = ImportacionExpertis::firstOrFail();
        $this->assertSame($this->usuario->id, $auditoria->user_id);
        $this->assertSame('manual', $auditoria->resumen['origen']);
        $this->assertSame(1, $auditoria->filas_insertadas);
        $this->assertSame('', $auditoria->ruta_archivo);
        $this->assertSame($auditoria->id, $pago->importacion_expertis_id);

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/pagos/manual', $datos)
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('pagos_expertis', 1);
        $this->assertDatabaseCount('importaciones_expertis', 2);
        $this->assertSame(
            ImportacionExpertis::ESTADO_DUPLICADO,
            ImportacionExpertis::latest('id')->firstOrFail()->estado,
        );
    }

    public function test_pago_manual_valida_cuenta_y_monto(): void
    {
        $this->actingAs($this->usuario)
            ->from('/expertis/importaciones/pagos?modo=manual')
            ->post('/expertis/importaciones/pagos/manual', [
                'fecha' => '2025-01-31',
                'cuenta' => '47752785',
                'monto' => 0,
            ])
            ->assertRedirect('/expertis/importaciones/pagos?modo=manual')
            ->assertSessionHasErrors(['cuenta', 'monto']);

        $this->assertDatabaseCount('pagos_expertis', 0);
        $this->assertDatabaseCount('importaciones_expertis', 0);
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

    private function crearXlsxConFechasExcel(array $fila): string
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'expertis_excel_'.Str::uuid().'.xlsx';
        $this->temporales[] = $ruta;

        $fila[8] = ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-07-06 00:00:00'));
        $fila[10] = ((18 * 60 * 60) + (30 * 60) + 40) / 86400;
        $fila[13] = ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-07-09 00:00:00'));

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::ENCABEZADOS_GESTIONES, null, 'A1');
        $sheet->fromArray($fila, null, 'A2');
        $sheet->getStyle('I2')->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        $sheet->getStyle('K2')->getNumberFormat()->setFormatCode('hh:mm:ss');
        $sheet->getStyle('N2')->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');

        $writer = new PhpSpreadsheetXlsxWriter($spreadsheet);
        $writer->save($ruta);
        $spreadsheet->disconnectWorksheets();

        return $ruta;
    }

    private function crearXlsxPagosConFechasExcel(array $filas): string
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pagos_excel_'.Str::uuid().'.xlsx';
        $this->temporales[] = $ruta;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            ['FECHA', 'CUENTA', 'MONTO', 'EJECUTIVO', 'TIPO DE ACUERDO', 'RECAUDO'],
            null,
            'A1',
        );

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;
            $fila[0] = ExcelDate::PHPToExcel(new \DateTimeImmutable($fila[0]));
            $sheet->fromArray($fila, null, 'A'.$numeroFila);
            $sheet->getStyle('A'.$numeroFila)
                ->getNumberFormat()
                ->setFormatCode('yyyy-mm-dd');
        }

        $writer = new PhpSpreadsheetXlsxWriter($spreadsheet);
        $writer->save($ruta);
        $spreadsheet->disconnectWorksheets();

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
