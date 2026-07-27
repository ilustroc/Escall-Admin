<?php

namespace Tests\Feature;

use App\Models\ImportacionExpertis;
use App\Models\User;
use App\Services\Expertis\ExpertisSpreadsheetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('large-import')]
class ExpertisAssignmentLargeImportTest extends TestCase
{
    private array $temporales = [];

    private ?string $baseSqlite = null;

    protected function setUp(): void
    {
        if (getenv('RUN_LARGE_IMPORT') === '1') {
            $this->baseSqlite = sys_get_temp_dir().DIRECTORY_SEPARATOR
                .'assignments_benchmark_'.Str::uuid().'.sqlite';
            touch($this->baseSqlite);
            putenv('DB_DATABASE='.$this->baseSqlite);
            $_ENV['DB_DATABASE'] = $this->baseSqlite;
            $_SERVER['DB_DATABASE'] = $this->baseSqlite;
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }

        if ($this->baseSqlite !== null) {
            DB::disconnect('sqlite');
        }

        parent::tearDown();

        if ($this->baseSqlite !== null) {
            if (is_file($this->baseSqlite)) {
                unlink($this->baseSqlite);
            }
            putenv('DB_DATABASE=:memory:');
            $_ENV['DB_DATABASE'] = ':memory:';
            $_SERVER['DB_DATABASE'] = ':memory:';
        }
    }

    public function test_importa_40000_asignaciones_por_streaming_y_bloquea_el_mismo_hash(): void
    {
        if (getenv('RUN_LARGE_IMPORT') !== '1') {
            $this->markTestSkipped(
                'Ejecutar expresamente con RUN_LARGE_IMPORT=1 y --group=large-import.',
            );
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        Storage::fake('local');
        config(['expertis.chunk_size' => 500]);
        $usuario = User::factory()->create();
        $archivo = $this->crearXlsxGrande(40000);

        $inicio = microtime(true);
        $preview = $this->actingAs($usuario)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => $this->uploadedCopy($archivo),
            ]);
        $preview
            ->assertOk()
            ->assertJsonPath('total_filas_detectadas', 40000)
            ->assertJsonPath('periodo_detectado', '202607');
        $this->assertCount(20, $preview->json('preview'));

        $this->actingAs($usuario)
            ->post('/expertis/importaciones/asignaciones', [
                'preview_token' => $preview->json('token'),
            ])
            ->assertRedirect();

        $segundos = microtime(true) - $inicio;
        $memoria = memory_get_peak_usage(true);
        $importacion = ImportacionExpertis::firstOrFail();

        $this->assertSame(40000, $importacion->total_filas);
        $this->assertSame(40000, $importacion->filas_insertadas);
        $this->assertSame(0, $importacion->filas_error);
        $this->assertSame(0, $importacion->filas_duplicadas);
        $this->assertSame(80, $importacion->resumen['lotes']);
        $this->assertDatabaseCount('asignaciones_expertis', 40000);

        $segunda = $this->actingAs($usuario)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => $this->uploadedCopy($archivo),
            ]);
        $segunda
            ->assertOk()
            ->assertJsonPath('archivo_duplicado.id', $importacion->id);

        $this->actingAs($usuario)
            ->post('/expertis/importaciones/asignaciones', [
                'preview_token' => $segunda->json('token'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('importaciones_expertis', 1);
        $this->assertDatabaseCount('asignaciones_expertis', 40000);

        fwrite(STDOUT, PHP_EOL.'BENCHMARK_ASIGNACIONES='.json_encode([
            'filas_procesadas' => $importacion->total_filas,
            'filas_insertadas' => $importacion->filas_insertadas,
            'lotes' => $importacion->resumen['lotes'],
            'errores' => $importacion->filas_error,
            'duplicadas' => $importacion->filas_duplicadas,
            'tiempo_segundos' => round($segundos, 3),
            'memoria_maxima_mb' => round($memoria / 1024 / 1024, 2),
        ], JSON_UNESCAPED_UNICODE).PHP_EOL);
    }

    private function crearXlsxGrande(int $cantidad): string
    {
        $ruta = $this->temporal('assignments_large_');
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(
            ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES,
        ));

        for ($i = 1; $i <= $cantidad; $i++) {
            $dni = str_pad((string) $i, 8, '0', STR_PAD_LEFT);
            $cartera = $i % 2 === 0 ? 'PRO' : 'LOS ANDES';
            $writer->addRow(Row::fromValues([
                202607,
                'EXPERTIS',
                $dni,
                'CLIENTE FICTICIO '.$i,
                '',
                $cartera,
                'COSECHA 2026',
                '-',
                'PRODUCTO FICTICIO',
                '-',
                'JULIO 2026',
                $i % 3 === 0 ? 'CUSCO' : 'LIMA',
                1000 + ($i / 100),
                500 + ($i / 100),
                '-',
                0,
                '2000-01-01',
                26,
                1,
                'NO',
                '-',
                '-',
                '-',
                $i % 2 === 0 ? 'M' : 'F',
                '-',
                2021,
            ]));
        }

        $writer->close();

        return $ruta;
    }

    private function uploadedCopy(string $origen): UploadedFile
    {
        $copia = $this->temporal('assignment_large_upload_');
        copy($origen, $copia);

        return new UploadedFile(
            $copia,
            'asignaciones_40000.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function temporal(string $prefijo): string
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefijo.Str::uuid().'.xlsx';
        $this->temporales[] = $ruta;

        return $ruta;
    }
}
