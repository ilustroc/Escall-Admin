<?php

namespace Tests\Feature;

use App\Jobs\Expertis\PrepararAsignacionExpertisImport;
use App\Jobs\Expertis\ProcesarAsignacionExpertisImport;
use App\Models\ImportacionExpertis;
use App\Models\User;
use App\Services\Expertis\AsignacionExpertisImportService;
use App\Services\Expertis\AsignacionExpertisPreparationService;
use App\Services\Expertis\ExpertisSpreadsheetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Tests\TestCase;

class ExpertisAssignmentQueueTest extends TestCase
{
    private array $temporaryFiles = [];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        config([
            'queue.default' => 'database',
            'cache.default' => 'array',
            'expertis.chunk_size' => 2,
            'expertis.heartbeat_rows' => 2,
        ]);
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_upload_responde_accepted_y_solo_despacha_preparacion(): void
    {
        $response = $this->upload($this->createXlsx([
            $this->row(),
        ]));

        $response
            ->assertAccepted()
            ->assertJsonPath('estado', 'validando')
            ->assertJsonStructure([
                'importacion_id',
                'seguimiento_url',
                'estado_url',
            ]);
        $import = ImportacionExpertis::firstOrFail();
        $this->assertSame(ImportacionExpertis::ESTADO_VALIDANDO, $import->estado);
        $this->assertNotNull($import->heartbeat_at);
        $this->assertDatabaseCount('asignaciones_expertis', 0);
        Queue::assertPushedOn(
            'expertis',
            PrepararAsignacionExpertisImport::class,
        );
        Queue::assertNotPushed(ProcesarAsignacionExpertisImport::class);
    }

    public function test_conexion_sync_impide_ejecutar_asignaciones_en_http(): void
    {
        config(['queue.default' => 'sync']);

        $this->upload($this->createXlsx([$this->row()]))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'La importación de asignaciones requiere un trabajador de cola. Configura QUEUE_CONNECTION=database e inicia php artisan queue:work.',
            );

        $this->assertDatabaseCount('importaciones_expertis', 0);
        $this->assertDatabaseCount('asignaciones_expertis', 0);
    }

    public function test_preparacion_crea_jsonl_preview_y_errores_sin_insertar(): void
    {
        $rows = [];
        for ($i = 1; $i <= 22; $i++) {
            $rows[] = $this->row([
                'dni' => str_pad((string) $i, 8, '0', STR_PAD_LEFT),
            ]);
        }
        $rows[] = $this->row([
            'dni' => '00000023',
            'anio_laboral' => 5,
        ]);
        $import = $this->prepare($this->createXlsx($rows));

        $this->assertSame(
            ImportacionExpertis::ESTADO_LISTO_PARA_IMPORTAR,
            $import->estado,
        );
        $this->assertSame(23, $import->total_filas);
        $this->assertSame(1, $import->filas_error);
        $this->assertSame(22, $import->resumen['filas_validas']);
        $this->assertCount(20, $import->resumen['preview']);
        $this->assertSame(11, $import->resumen['total_chunks']);
        $this->assertTrue($import->resumen['preparacion_completada']);
        $this->assertNotNull($import->heartbeat_at);
        $this->assertDatabaseCount('errores_importacion_expertis', 1);
        $this->assertDatabaseCount('asignaciones_expertis', 0);
        $this->assertCount(
            11,
            app(AsignacionExpertisPreparationService::class)
                ->chunks($import),
        );
    }

    public function test_confirmacion_despacha_importacion_que_lee_chunks_sin_xlsx(): void
    {
        $import = $this->prepare($this->createXlsx([
            $this->row(),
            $this->row(['dni' => '00000002']),
            $this->row(['dni' => '00000003']),
        ]));
        Storage::disk('local')->delete($import->ruta_archivo);

        $this->actingAs($this->user)
            ->post('/expertis/importaciones/asignaciones', [
                'importacion_id' => $import->id,
            ])
            ->assertRedirect(
                route('expertis.importaciones.show', $import),
            );
        $import->refresh();
        $this->assertSame(ImportacionExpertis::ESTADO_EN_COLA, $import->estado);
        $this->assertNotNull($import->heartbeat_at);
        Queue::assertPushedOn(
            'expertis',
            ProcesarAsignacionExpertisImport::class,
        );

        app(AsignacionExpertisImportService::class)
            ->importarPreparada($import);
        $import->refresh();

        $this->assertSame(ImportacionExpertis::ESTADO_COMPLETADO, $import->estado);
        $this->assertSame(3, $import->progreso_actual);
        $this->assertSame(3, $import->progreso_total);
        $this->assertSame(3, $import->filas_insertadas);
        $this->assertDatabaseCount('asignaciones_expertis', 3);
        $this->assertSame(
            [],
            app(AsignacionExpertisPreparationService::class)->chunks($import),
        );
    }

    public function test_endpoint_estado_es_autenticado_y_no_expone_rutas_ni_hash(): void
    {
        $import = $this->prepare($this->createXlsx([$this->row()]));

        Auth::logout();
        $this->get("/expertis/importaciones/{$import->id}/estado")
            ->assertRedirect(route('login'));

        $this->actingAs($this->user)
            ->getJson("/expertis/importaciones/{$import->id}/estado")
            ->assertOk()
            ->assertJsonPath('estado', 'listo_para_importar')
            ->assertJsonPath('progreso_actual', 1)
            ->assertJsonPath('progreso_total', 1)
            ->assertJsonPath('porcentaje', 100)
            ->assertJsonPath('puede_confirmar', true)
            ->assertJsonMissingPath('ruta_archivo')
            ->assertJsonMissingPath('ruta_chunks')
            ->assertJsonMissingPath('hash_archivo');
    }

    public function test_failed_marca_fallido_con_mensaje_seguro_y_conserva_archivos(): void
    {
        $import = $this->prepare($this->createXlsx([$this->row()]));
        $chunks = app(AsignacionExpertisPreparationService::class)
            ->chunks($import);

        (new ProcesarAsignacionExpertisImport($import->id))
            ->failed(new RuntimeException('stack privado con datos'));
        $import->refresh();

        $this->assertSame(ImportacionExpertis::ESTADO_FALLIDO, $import->estado);
        $this->assertStringNotContainsString(
            'stack privado',
            $import->mensaje_error,
        );
        $this->assertNotNull($import->finalizado_at);
        Storage::disk('local')->assertExists($import->ruta_archivo);
        foreach ($chunks as $chunk) {
            Storage::disk('local')->assertExists($chunk);
        }
    }

    public function test_lock_impide_dos_preparaciones_simultaneas(): void
    {
        $response = $this->upload($this->createXlsx([$this->row()]));
        $import = ImportacionExpertis::query()->findOrFail(
            $response->json('importacion_id'),
        );
        $lock = Cache::lock('expertis-importacion-'.$import->id, 60);
        $this->assertTrue($lock->get());

        try {
            (new PrepararAsignacionExpertisImport($import->id))
                ->handle(app(AsignacionExpertisPreparationService::class));
        } finally {
            $lock->release();
        }

        $this->assertSame(
            ImportacionExpertis::ESTADO_VALIDANDO,
            $import->refresh()->estado,
        );
        $this->assertSame(
            [],
            app(AsignacionExpertisPreparationService::class)->chunks($import),
        );
    }

    public function test_jobs_completan_las_dos_fases(): void
    {
        $response = $this->upload($this->createXlsx([$this->row()]));
        $import = ImportacionExpertis::query()->findOrFail(
            $response->json('importacion_id'),
        );

        (new PrepararAsignacionExpertisImport($import->id))
            ->handle(app(AsignacionExpertisPreparationService::class));
        $this->assertSame(
            ImportacionExpertis::ESTADO_LISTO_PARA_IMPORTAR,
            $import->refresh()->estado,
        );

        $import->update([
            'estado' => ImportacionExpertis::ESTADO_EN_COLA,
            'fase' => ImportacionExpertis::FASE_IMPORTANDO,
        ]);
        (new ProcesarAsignacionExpertisImport($import->id))
            ->handle(app(AsignacionExpertisImportService::class));

        $this->assertSame(
            ImportacionExpertis::ESTADO_COMPLETADO,
            $import->refresh()->estado,
        );
        $this->assertDatabaseCount('asignaciones_expertis', 1);
    }

    public function test_reintento_prioriza_chunks_preparados(): void
    {
        $import = $this->prepare($this->createXlsx([$this->row()]));
        $import->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'finalizado_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/expertis/importaciones/{$import->id}/reintentar")
            ->assertAccepted()
            ->assertJsonPath('estado', 'en_cola')
            ->assertJsonPath('intentos', 2);

        Queue::assertPushed(ProcesarAsignacionExpertisImport::class);
    }

    public function test_reintento_desde_chunks_continua_despues_del_ultimo_confirmado(): void
    {
        $import = $this->prepare($this->createXlsx([
            $this->row(),
            $this->row(['dni' => '00000002']),
            $this->row(['dni' => '00000003']),
        ]));
        $chunks = app(AsignacionExpertisPreparationService::class)
            ->chunks($import);
        $firstRows = array_values(array_filter(array_map(
            fn (string $line): ?array => json_decode($line, true),
            preg_split(
                '/\R/',
                trim(Storage::disk('local')->get($chunks[0])),
            ),
        )));
        DB::table('asignaciones_expertis')->insert($firstRows);
        $summary = $import->resumen;
        $summary['ultimo_chunk_procesado'] = 1;
        $import->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'progreso_actual' => 2,
            'progreso_total' => 3,
            'filas_insertadas' => 2,
            'resumen' => $summary,
            'finalizado_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/expertis/importaciones/{$import->id}/reintentar")
            ->assertAccepted()
            ->assertJsonPath('estado', 'en_cola')
            ->assertJsonPath('progreso_actual', 2);
        app(AsignacionExpertisImportService::class)
            ->importarPreparada($import->refresh());
        $import->refresh();

        $this->assertSame(3, $import->filas_insertadas);
        $this->assertSame(0, $import->filas_duplicadas);
        $this->assertSame(3, $import->progreso_actual);
        $this->assertDatabaseCount('asignaciones_expertis', 3);
    }

    public function test_reintento_repite_preparacion_si_solo_existe_xlsx(): void
    {
        $response = $this->upload($this->createXlsx([$this->row()]));
        $import = ImportacionExpertis::query()->findOrFail(
            $response->json('importacion_id'),
        );
        $import->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'finalizado_at' => now(),
        ]);
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson("/expertis/importaciones/{$import->id}/reintentar")
            ->assertAccepted()
            ->assertJsonPath('estado', 'validando')
            ->assertJsonPath('intentos', 2);

        Queue::assertPushed(PrepararAsignacionExpertisImport::class);
    }

    public function test_comando_recover_stale_simula_y_luego_marca_fallida(): void
    {
        $response = $this->upload($this->createXlsx([$this->row()]));
        $import = ImportacionExpertis::query()->findOrFail(
            $response->json('importacion_id'),
        );
        $import->update([
            'heartbeat_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);

        $this->artisan(
            'expertis:imports:recover-stale --minutes=5 --dry-run',
        )
            ->expectsOutputToContain((string) $import->id)
            ->assertSuccessful();
        $this->assertSame(
            ImportacionExpertis::ESTADO_VALIDANDO,
            $import->refresh()->estado,
        );

        $this->artisan('expertis:imports:recover-stale --minutes=5')
            ->assertSuccessful();
        $this->assertSame(
            ImportacionExpertis::ESTADO_FALLIDO,
            $import->refresh()->estado,
        );
        Storage::disk('local')->assertExists($import->ruta_archivo);
    }

    public function test_limpieza_respeta_dry_run_y_no_borra_estados_reintentables(): void
    {
        $completed = $this->prepare($this->createXlsx([$this->row()]));
        $completed->update([
            'estado' => ImportacionExpertis::ESTADO_COMPLETADO,
            'finalizado_at' => now(),
        ]);
        $failed = $this->prepare($this->createXlsx([
            $this->row(['dni' => '00000002']),
        ]));
        $failed->update([
            'estado' => ImportacionExpertis::ESTADO_FALLIDO,
            'finalizado_at' => now(),
        ]);

        $completedChunks = app(AsignacionExpertisPreparationService::class)
            ->chunks($completed);
        $failedChunks = app(AsignacionExpertisPreparationService::class)
            ->chunks($failed);
        foreach (array_merge($completedChunks, $failedChunks) as $chunk) {
            touch(
                Storage::disk('local')->path($chunk),
                now()->subDays(8)->timestamp,
            );
        }

        $this->artisan('expertis:prepared:cleanup --days=7 --dry-run')
            ->assertSuccessful();
        Storage::disk('local')->assertExists($completedChunks[0]);

        $this->artisan('expertis:prepared:cleanup --days=7')
            ->assertSuccessful();
        Storage::disk('local')->assertMissing($completedChunks[0]);
        Storage::disk('local')->assertExists($failedChunks[0]);
    }

    public function test_interfaz_declara_polling_de_tres_segundos_y_estados_terminales(): void
    {
        $source = file_get_contents(resource_path(
            'js/Pages/Expertis/Importaciones/Index.vue',
        ));

        $this->assertStringContainsString(
            'window.setInterval(refreshStatus, 3000)',
            $source,
        );
        $this->assertStringContainsString(
            "const activeStates = ['validando', 'en_cola', 'procesando']",
            $source,
        );
    }

    private function prepare(string $path): ImportacionExpertis
    {
        $response = $this->upload($path);
        $import = ImportacionExpertis::query()->findOrFail(
            $response->json('importacion_id'),
        );
        app(AsignacionExpertisPreparationService::class)->preparar($import);

        return $import->refresh();
    }

    private function upload(string $path)
    {
        return $this->actingAs($this->user)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => $this->uploadedCopy($path),
            ]);
    }

    private function createXlsx(array $rows): string
    {
        $path = $this->temporaryPath();
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(
            ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES,
        ));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    private function row(array $changes = []): array
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
        ], $changes));
    }

    private function uploadedCopy(string $source): UploadedFile
    {
        $copy = $this->temporaryPath();
        copy($source, $copy);

        return new UploadedFile(
            $copy,
            'asignaciones_ficticias.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function temporaryPath(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'expertis_queue_'.Str::uuid().'.xlsx';
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
