<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UploadLimit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpertisUploadDiagnosticsTest extends TestCase
{
    private array $temporaryFiles = [];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_mensaje_archivo_required_esta_en_espanol(): void
    {
        $this->actingAs($this->user)
            ->postJson('/expertis/importaciones/asignaciones/preview')
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.archivo.0',
                'Selecciona un archivo XLSX.',
            );
    }

    public function test_mensaje_archivo_mimes_esta_en_espanol(): void
    {
        $this->actingAs($this->user)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => UploadedFile::fake()->create(
                    'asignaciones.csv',
                    1,
                    'text/csv',
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.archivo.0',
                'El archivo debe ser un XLSX válido.',
            );
    }

    public function test_mensaje_archivo_max_esta_en_espanol(): void
    {
        $this->app->instance(
            UploadLimit::class,
            new UploadLimit(1, '64M', '70M'),
        );

        $this->actingAs($this->user)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => UploadedFile::fake()->create(
                    'asignaciones.xlsx',
                    1025,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.archivo.0',
                'El archivo excede el tamaño máximo permitido.',
            );
    }

    public function test_mensaje_archivo_uploaded_se_aplica_a_los_tres_importadores(): void
    {
        foreach (['asignaciones', 'gestiones', 'pagos'] as $type) {
            $this->actingAs($this->user)
                ->postJson("/expertis/importaciones/{$type}/preview", [
                    'archivo' => $this->invalidUpload(UPLOAD_ERR_FORM_SIZE),
                ])
                ->assertUnprocessable()
                ->assertJsonPath(
                    'errors.archivo.0',
                    'El archivo supera el tamaño permitido por el formulario.',
                );
        }
    }

    public function test_upload_err_ini_size_incluye_upload_max_filesize(): void
    {
        $this->actingAs($this->user)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => $this->invalidUpload(UPLOAD_ERR_INI_SIZE),
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.archivo.0',
                fn (string $message): bool => str_contains(
                    $message,
                    'upload_max_filesize configurado en PHP ('
                    .ini_get('upload_max_filesize').')',
                ),
            );
    }

    public function test_upload_err_partial_tiene_mensaje_accionable(): void
    {
        $this->assertUploadErrorMessage(
            UPLOAD_ERR_PARTIAL,
            'El archivo se subió parcialmente. Vuelve a seleccionarlo e intenta nuevamente.',
        );
    }

    public function test_upload_err_no_tmp_dir_tiene_mensaje_accionable(): void
    {
        $this->assertUploadErrorMessage(
            UPLOAD_ERR_NO_TMP_DIR,
            'El servidor no tiene disponible una carpeta temporal para recibir el archivo.',
        );
    }

    public function test_upload_err_cant_write_tiene_mensaje_accionable(): void
    {
        $this->assertUploadErrorMessage(
            UPLOAD_ERR_CANT_WRITE,
            'El servidor no pudo escribir el archivo en el almacenamiento temporal.',
        );
    }

    public function test_post_too_large_devuelve_json_413_con_error_de_archivo(): void
    {
        $request = Request::create(
            '/expertis/importaciones/asignaciones/preview',
            'POST',
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_LENGTH' => 10 * 1024 * 1024,
            ],
        );

        $response = app(ExceptionHandler::class)->render(
            $request,
            new PostTooLargeException,
        );
        $data = $response->getData(true);

        $this->assertSame(413, $response->getStatusCode());
        $this->assertSame(
            'El archivo supera el límite total permitido por el servidor.',
            $data['message'],
        );
        $this->assertSame(
            'El archivo supera post_max_size configurado en PHP ('
            .ini_get('post_max_size').').',
            $data['errors']['archivo'][0],
        );
        $this->assertSame(
            (string) ini_get('upload_max_filesize'),
            $data['upload_limit']['upload_max_filesize'],
        );
    }

    public function test_los_tres_controladores_exponen_max_bytes_efectivo(): void
    {
        $this->app->instance(
            UploadLimit::class,
            new UploadLimit(50, '2M', '8M'),
        );

        foreach (['asignaciones', 'gestiones', 'pagos'] as $type) {
            $this->actingAs($this->user)
                ->get("/expertis/importaciones/{$type}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('configuracion.max_bytes', 2 * 1024 * 1024)
                    ->where('configuracion.max_mb', 2)
                    ->where('configuracion.configured_max_mb', 50)
                    ->where('configuracion.upload_max_filesize', '2M')
                    ->where('configuracion.post_max_size', '8M'));
        }
    }

    private function assertUploadErrorMessage(int $error, string $message): void
    {
        $this->actingAs($this->user)
            ->postJson('/expertis/importaciones/asignaciones/preview', [
                'archivo' => $this->invalidUpload($error),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', $message);
    }

    private function invalidUpload(int $error): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'expertis_upload_');
        file_put_contents($path, str_repeat('x', 128));
        $this->temporaryFiles[] = $path;

        return new UploadedFile(
            $path,
            'expertis.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $error,
            true,
        );
    }
}
