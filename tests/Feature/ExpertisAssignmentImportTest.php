<?php

namespace Tests\Feature;

use App\Models\AsignacionExpertis;
use App\Models\ImportacionExpertis;
use App\Models\User;
use App\Services\Expertis\ExpertisSpreadsheetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as PhpSpreadsheetXlsxWriter;
use Tests\TestCase;

class ExpertisAssignmentImportTest extends TestCase
{
    private array $temporales = [];

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
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

    public function test_usuario_no_autenticado_no_accede_a_asignaciones(): void
    {
        $this->get('/expertis/importaciones/asignaciones')
            ->assertRedirect(route('login'));

        $this->post('/expertis/importaciones/asignaciones/preview')
            ->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_ve_la_pagina_de_carga(): void
    {
        $this->actingAs($this->usuario)
            ->get('/expertis/importaciones/asignaciones')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expertis/Importaciones/Asignaciones')
                ->where('configuracion.extensiones.0', 'xlsx'));
    }

    public function test_archivo_es_obligatorio(): void
    {
        $this->actingAs($this->usuario)
            ->postJson('/expertis/importaciones/asignaciones/preview')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');
    }

    public function test_solo_se_aceptan_archivos_xlsx_reales(): void
    {
        $this->actingAs($this->usuario)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => UploadedFile::fake()->create('asignaciones.csv', 1, 'text/csv'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');
    }

    public function test_preview_reconoce_encabezados_y_escanea_todo_sin_retener_mas_de_20_filas(): void
    {
        $filas = [];
        for ($i = 1; $i <= 25; $i++) {
            $filas[] = $this->fila(['dni' => str_pad((string) $i, 8, '0', STR_PAD_LEFT)]);
        }

        $preview = $this->preview($this->crearXlsx($filas));

        $preview
            ->assertOk()
            ->assertJsonPath('columnas_faltantes', [])
            ->assertJsonPath('periodo_detectado', '202607')
            ->assertJsonPath('empresa_detectada', 'EXPERTIS')
            ->assertJsonPath('total_filas_detectadas', 25)
            ->assertJsonPath('preview.0.codigo', '00000001-LOS ANDES');
        $this->assertCount(20, $preview->json('preview'));
    }

    public function test_encabezado_faltante_impide_confirmar(): void
    {
        $encabezados = ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES;
        $fila = $this->fila();
        unset($encabezados[4], $fila[4]);

        $this->preview($this->crearXlsx(
            [array_values($fila)],
            array_values($encabezados),
        ))
            ->assertOk()
            ->assertJsonPath('token', null)
            ->assertJsonPath('columnas_faltantes.0', 'CODIGO');
    }

    public function test_varios_periodos_incluso_despues_de_la_fila_20_bloquean_preview(): void
    {
        $filas = [];
        for ($i = 1; $i <= 24; $i++) {
            $filas[] = $this->fila(['dni' => str_pad((string) $i, 8, '0', STR_PAD_LEFT)]);
        }
        $filas[] = $this->fila(['periodo' => '202608', 'dni' => '00000025']);

        $this->preview($this->crearXlsx($filas))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'El archivo debe contener un único periodo de asignación.',
            );
    }

    public function test_empresa_distinta_de_expertis_bloquea_preview(): void
    {
        $this->preview($this->crearXlsx([
            $this->fila(['empresa' => 'OTRA EMPRESA']),
        ]))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'El archivo de asignación contiene una empresa distinta de EXPERTIS.',
            );
    }

    public function test_insercion_inicial_guarda_los_26_campos_normalizados(): void
    {
        $importacion = $this->importar($this->crearXlsx([$this->fila()]));

        $this->assertDatabaseCount('asignaciones_expertis', 1);
        $asignacion = AsignacionExpertis::firstOrFail();
        $this->assertSame('202607', $asignacion->periodo);
        $this->assertSame('00000001', $asignacion->dni);
        $this->assertSame('00000001-LOS ANDES', $asignacion->codigo);
        $this->assertSame('2994.71', $asignacion->deuda_total);
        $this->assertNull($asignacion->sub_cosecha);
        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame('202607', $importacion->resumen['periodo']);
        $this->assertNull($importacion->fecha_minima);
        $this->assertNull($importacion->fecha_maxima);
    }

    public function test_fecha_nativa_de_excel_se_importa_en_anio_nacimiento(): void
    {
        $importacion = $this->importar($this->crearXlsxConFechaNativa());

        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame(
            '1999-04-25',
            AsignacionExpertis::firstOrFail()->fecha_nacimiento->toDateString(),
        );
    }

    public function test_codigo_incorrecto_se_registra_como_error_de_fila(): void
    {
        $importacion = $this->importar($this->crearXlsx([
            $this->fila(['codigo' => '00000001-PRO']),
        ]));

        $this->assertDatabaseCount('asignaciones_expertis', 0);
        $this->assertSame(1, $importacion->filas_error);
        $this->assertDatabaseHas('errores_importacion_expertis', [
            'importacion_expertis_id' => $importacion->id,
            'mensaje' => 'El código no coincide con el DNI y el tipo de cartera para Expertis.',
        ]);
    }

    public function test_clave_repetida_identica_dentro_del_archivo_es_duplicada(): void
    {
        $fila = $this->fila();
        $importacion = $this->importar($this->crearXlsx([$fila, $fila]));

        $this->assertDatabaseCount('asignaciones_expertis', 1);
        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame(1, $importacion->filas_duplicadas);
        $this->assertSame(0, $importacion->filas_error);
    }

    public function test_clave_repetida_con_datos_distintos_registra_error(): void
    {
        $importacion = $this->importar($this->crearXlsx([
            $this->fila(),
            $this->fila(['deuda_total' => 3200]),
        ]));

        $this->assertDatabaseCount('asignaciones_expertis', 1);
        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame(1, $importacion->filas_error);
        $this->assertDatabaseHas('errores_importacion_expertis', [
            'mensaje' => 'La clave PERIODO + EMPRESA + CODIGO está repetida con información diferente dentro del archivo.',
        ]);
    }

    public function test_reimportacion_identica_con_otro_hash_cuenta_duplicado(): void
    {
        $this->importar($this->crearXlsx([$this->fila()]));
        $segunda = $this->importar($this->crearXlsx([$this->fila()], null, true));

        $this->assertDatabaseCount('asignaciones_expertis', 1);
        $this->assertSame(0, $segunda->filas_insertadas);
        $this->assertSame(1, $segunda->filas_duplicadas);
    }

    public function test_reimportacion_modificada_actualiza_sin_borrar_el_periodo(): void
    {
        $this->importar($this->crearXlsx([
            $this->fila(),
            $this->fila(['dni' => '00000002']),
        ]));
        $segunda = $this->importar($this->crearXlsx([
            $this->fila(['deuda_total' => 3500]),
        ]));

        $this->assertDatabaseCount('asignaciones_expertis', 2);
        $this->assertSame(1, $segunda->filas_actualizadas);
        $this->assertSame(
            '3500.00',
            AsignacionExpertis::where('dni', '00000001')->firstOrFail()->deuda_total,
        );
        $this->assertDatabaseHas('asignaciones_expertis', ['dni' => '00000002']);
    }

    public function test_mismo_codigo_en_periodos_diferentes_inserta_dos_registros(): void
    {
        $this->importar($this->crearXlsx([$this->fila()]));
        $this->importar($this->crearXlsx([$this->fila(['periodo' => '202608'])]));

        $this->assertDatabaseCount('asignaciones_expertis', 2);
        $this->assertSame(
            ['202607', '202608'],
            AsignacionExpertis::orderBy('periodo')->pluck('periodo')->all(),
        );
    }

    public function test_procesa_varios_lotes_y_guarda_progreso_final(): void
    {
        config(['expertis.chunk_size' => 2]);
        $filas = [];
        for ($i = 1; $i <= 5; $i++) {
            $filas[] = $this->fila(['dni' => str_pad((string) $i, 8, '0', STR_PAD_LEFT)]);
        }

        $importacion = $this->importar($this->crearXlsx($filas));

        $this->assertSame(5, $importacion->total_filas);
        $this->assertSame(5, $importacion->filas_insertadas);
        $this->assertSame(3, $importacion->resumen['lotes']);
        $this->assertDatabaseCount('asignaciones_expertis', 5);
    }

    public function test_errores_de_validacion_se_insertan_por_el_flujo_generico(): void
    {
        config(['expertis.chunk_size' => 2]);
        $importacion = $this->importar($this->crearXlsx([
            $this->fila(['sexo' => 'X']),
            $this->fila(['dni' => '00000002', 'deuda_capital' => '-']),
            $this->fila(['dni' => '00000003']),
        ]));

        $this->assertSame(3, $importacion->total_filas);
        $this->assertSame(1, $importacion->filas_insertadas);
        $this->assertSame(2, $importacion->filas_error);
        $this->assertDatabaseCount('errores_importacion_expertis', 2);
    }

    public function test_reintento_recupera_filas_confirmadas_antes_de_interrupcion(): void
    {
        $archivo = $this->crearXlsx([$this->fila()]);
        $importacion = $this->importar($archivo);
        $importacion->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'total_filas' => 0,
            'filas_insertadas' => 0,
            'filas_actualizadas' => 0,
            'filas_duplicadas' => 0,
            'filas_error' => 0,
            'finalizado_at' => now(),
            'mensaje_error' => 'Ejecución interrumpida.',
        ]);

        $recuperada = $this->importar($archivo);

        $this->assertSame($importacion->id, $recuperada->id);
        $this->assertSame(1, $recuperada->filas_insertadas);
        $this->assertSame(0, $recuperada->filas_duplicadas);
        $this->assertDatabaseCount('asignaciones_expertis', 1);
    }

    public function test_archivo_completado_con_el_mismo_hash_no_se_procesa_dos_veces(): void
    {
        $archivo = $this->crearXlsx([$this->fila()]);
        $primera = $this->importar($archivo);
        $preview = $this->preview($archivo);
        $preview->assertJsonPath('archivo_duplicado.id', $primera->id);

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/asignaciones', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('importaciones_expertis', 1);
        $this->assertDatabaseCount('asignaciones_expertis', 1);
    }

    public function test_plantilla_tiene_26_encabezados_y_tres_filas_ficticias(): void
    {
        $respuesta = $this->actingAs($this->usuario)
            ->get('/expertis/importaciones/asignaciones/plantilla');
        $respuesta
            ->assertOk()
            ->assertDownload('plantilla_asignaciones_expertis.xlsx');

        $ruta = $this->temporal('assignment_template_');
        file_put_contents($ruta, $respuesta->streamedContent());
        $filas = $this->leerXlsx($ruta);

        $this->assertSame(ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES, $filas[0]);
        $this->assertCount(4, $filas);
        $this->assertSame('00000001', $filas[1][2]);
        $this->assertSame('00000003', $filas[3][2]);
    }

    public function test_historial_reconoce_y_filtra_asignaciones(): void
    {
        $this->importar($this->crearXlsx([$this->fila()]));

        $this->actingAs($this->usuario)
            ->get('/expertis/importaciones?tipo=asignaciones')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expertis/Importaciones/Index')
                ->where('importaciones.data.0.tipo', 'asignaciones')
                ->where('importaciones.data.0.resumen.periodo', '202607')
                ->where('tipos.0.label', 'Asignaciones'));
    }

    public function test_listado_es_paginado_y_selecciona_el_ultimo_periodo(): void
    {
        $this->importar($this->crearXlsx([$this->fila()]));
        $this->importar($this->crearXlsx([$this->fila(['periodo' => '202608'])]));

        $this->actingAs($this->usuario)
            ->get('/expertis/asignaciones')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expertis/Asignaciones/Index')
                ->where('filtros.periodo', '202608')
                ->where('asignaciones.per_page', 50)
                ->has('asignaciones.data', 1));
    }

    public function test_listado_filtra_periodo(): void
    {
        $this->importar($this->crearXlsx([$this->fila()]));
        $this->importar($this->crearXlsx([$this->fila(['periodo' => '202608'])]));

        $this->actingAs($this->usuario)
            ->get('/expertis/asignaciones?periodo=202607')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filtros.periodo', '202607')
                ->where('asignaciones.data.0.periodo', '202607')
                ->has('asignaciones.data', 1));
    }

    public function test_exportacion_xlsx_conserva_dni_codigo_y_montos(): void
    {
        $this->importar($this->crearXlsx([$this->fila()]));

        $respuesta = $this->actingAs($this->usuario)
            ->get('/expertis/asignaciones/export?periodo=202607');
        $respuesta->assertOk()->assertDownload();

        $ruta = $respuesta->baseResponse->getFile()->getPathname();
        $filas = $this->leerXlsx($ruta);

        $this->assertSame(ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES, $filas[0]);
        $this->assertSame('00000001', $filas[1][2]);
        $this->assertSame('00000001-LOS ANDES', $filas[1][4]);
        $this->assertStringNotContainsString("'", $filas[1][2]);
        $this->assertIsNumeric($filas[1][12]);
        $this->assertSame('25/04/1999', $filas[1][16]);
    }

    private function importar(string $archivo): ImportacionExpertis
    {
        $preview = $this->preview($archivo);
        $preview->assertOk()->assertJsonPath('columnas_faltantes', []);

        $this->actingAs($this->usuario)
            ->post('/expertis/importaciones/asignaciones', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        return ImportacionExpertis::latest('id')->firstOrFail();
    }

    private function preview(string $archivo)
    {
        return $this->actingAs($this->usuario)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => $this->uploadedCopy($archivo),
            ]);
    }

    private function fila(array $cambios = []): array
    {
        return array_values(array_merge([
            'periodo' => '202607',
            'empresa' => 'EXPERTIS',
            'dni' => '00000001',
            'titular' => 'CLIENTE FICTICIO',
            'codigo' => '',
            'tipo_cartera' => 'LOS ANDES',
            'cosecha' => 'COSECHA 2026',
            'sub_cosecha' => '-',
            'producto' => 'S4',
            'sub_producto' => 'B',
            'historico' => 'JULIO 2026',
            'departamento' => 'PUNO',
            'deuda_total' => 2994.71,
            'deuda_capital' => 1190.85,
            'campania' => 476.34,
            'porcentaje' => 0,
            'fecha_nacimiento' => '25/04/1999',
            'edad' => 27,
            'entidades' => 1,
            'negocio' => 'NO',
            'sueldo' => '-',
            'situacion_laboral' => '-',
            'anio_laboral' => '-',
            'sexo' => 'F',
            'rango_sueldo' => '-',
            'anio_castigo' => 2021,
        ], $cambios));
    }

    private function crearXlsx(
        array $filas,
        ?array $encabezados = null,
        bool $agregarFilaVacia = false,
    ): string {
        $ruta = $this->temporal('assignments_');
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(
            $encabezados ?? ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES,
        ));
        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }
        if ($agregarFilaVacia) {
            $writer->addRow(Row::fromValues(array_fill(0, 26, '')));
        }
        $writer->close();

        return $ruta;
    }

    private function uploadedCopy(string $origen): UploadedFile
    {
        $copia = $this->temporal('assignment_upload_');
        copy($origen, $copia);

        return new UploadedFile(
            $copia,
            'asignaciones_expertis.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function crearXlsxConFechaNativa(): string
    {
        $ruta = $this->temporal('assignment_excel_date_');
        $fila = $this->fila();
        $fila[16] = ExcelDate::PHPToExcel(new \DateTimeImmutable('1999-04-25'));

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES,
            null,
            'A1',
        );
        $sheet->fromArray($fila, null, 'A2');
        $sheet->getStyle('Q2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        $writer = new PhpSpreadsheetXlsxWriter($spreadsheet);
        $writer->save($ruta);
        $spreadsheet->disconnectWorksheets();

        return $ruta;
    }

    private function leerXlsx(string $ruta): array
    {
        $filas = [];
        $reader = new Reader;
        $reader->open($ruta);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $filas[] = $row->toArray();
            }
            break;
        }
        $reader->close();

        return $filas;
    }

    private function temporal(string $prefijo): string
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefijo.Str::uuid().'.xlsx';
        $this->temporales[] = $ruta;

        return $ruta;
    }
}
