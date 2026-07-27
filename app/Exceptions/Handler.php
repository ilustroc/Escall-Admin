<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (
            PostTooLargeException $exception,
            Request $request,
        ) {
            if (! $request->expectsJson()) {
                return null;
            }

            Log::warning('Expertis rechazó un archivo antes de leer el XLSX.', [
                'modulo' => 'Expertis',
                'ruta_solicitada' => $request->route()?->getName() ?? $request->path(),
                'codigo_carga_php' => null,
                'tamano_informado' => null,
                'content_length' => $request->server('CONTENT_LENGTH'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'usuario_id' => $request->user()?->id,
            ]);

            $postMaxSize = (string) ini_get('post_max_size');

            return response()->json([
                'message' => 'El archivo supera el límite total permitido por el servidor.',
                'errors' => [
                    'archivo' => [
                        "El archivo supera post_max_size configurado en PHP ({$postMaxSize}).",
                    ],
                ],
                'upload_limit' => [
                    'post_max_size' => $postMaxSize,
                    'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                ],
            ], 413);
        });
    }
}
