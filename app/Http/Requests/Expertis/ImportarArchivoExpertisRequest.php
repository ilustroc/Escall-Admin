<?php

namespace App\Http\Requests\Expertis;

use App\Support\UploadLimit;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use ZipArchive;

abstract class ImportarArchivoExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'archivo' => [
                'bail',
                'required',
                'file',
                'mimes:xlsx',
                'max:'.app(UploadLimit::class)->maxKilobytes(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona un archivo XLSX.',
            'archivo.file' => 'El archivo XLSX debe ser un archivo válido.',
            'archivo.uploaded' => $this->nativeUploadErrorMessage(),
            'archivo.mimes' => 'El archivo debe ser un XLSX válido.',
            'archivo.extensions' => 'La extensión del archivo debe ser .xlsx.',
            'archivo.mimetypes' => 'El tipo de contenido del archivo debe corresponder a un XLSX válido.',
            'archivo.max' => 'El archivo excede el tamaño máximo permitido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'archivo' => 'archivo XLSX',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $archivo = $this->file('archivo');
                if (! $archivo || ! $archivo->isValid()) {
                    return;
                }

                if (mb_strtolower($archivo->getClientOriginalExtension()) !== 'xlsx') {
                    $validator->errors()->add('archivo', 'La extensión del archivo debe ser .xlsx.');

                    return;
                }

                $zip = new ZipArchive;
                $abierto = $zip->open($archivo->getRealPath());
                $esXlsx = $abierto === true
                    && $zip->locateName('[Content_Types].xml') !== false
                    && $zip->locateName('xl/workbook.xml') !== false;

                if ($abierto === true) {
                    $zip->close();
                }

                if (! $esXlsx) {
                    $validator->errors()->add(
                        'archivo',
                        'El contenido no corresponde a un libro XLSX válido.',
                    );
                }
            },
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function failedValidation(ValidatorContract $validator): void
    {
        $archivo = $this->file('archivo');

        if ($archivo instanceof UploadedFile && $archivo->getError() !== UPLOAD_ERR_OK) {
            Log::warning('Expertis rechazó un archivo antes de leer el XLSX.', [
                'modulo' => 'Expertis',
                'ruta_solicitada' => $this->route()?->getName() ?? $this->path(),
                'codigo_carga_php' => $archivo->getError(),
                'tamano_informado' => $this->reportedFileSize($archivo),
                'content_length' => $this->server('CONTENT_LENGTH'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'usuario_id' => $this->user()?->id,
            ]);
        }

        parent::failedValidation($validator);
    }

    protected function nativeUploadErrorMessage(): string
    {
        $archivo = $this->file('archivo');
        $error = $archivo instanceof UploadedFile
            ? $archivo->getError()
            : UPLOAD_ERR_NO_FILE;

        return match ($error) {
            UPLOAD_ERR_INI_SIZE => sprintf(
                'El archivo supera el límite upload_max_filesize configurado en PHP (%s). El archivo seleccionado pesa %s.',
                (string) ini_get('upload_max_filesize'),
                $this->formatFileSize($this->reportedFileSize($archivo)),
            ),
            UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño permitido por el formulario.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente. Vuelve a seleccionarlo e intenta nuevamente.',
            UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene disponible una carpeta temporal para recibir el archivo.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo en el almacenamiento temporal.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga del archivo.',
            UPLOAD_ERR_OK => 'El servidor rechazó la subida del archivo. Revisa el límite de tamaño y la configuración temporal de PHP.',
            default => sprintf(
                'No se pudo completar la subida del archivo. Código de carga PHP: %d.',
                $error,
            ),
        };
    }

    private function reportedFileSize(?UploadedFile $archivo): ?int
    {
        if (! $archivo || ! is_file($archivo->getPathname())) {
            return null;
        }

        try {
            $size = $archivo->getSize();
        } catch (\RuntimeException) {
            return null;
        }

        return is_int($size) && $size > 0 ? $size : null;
    }

    private function formatFileSize(?int $bytes): string
    {
        if ($bytes === null) {
            return 'un tamaño no disponible';
        }

        return number_format($bytes / 1048576, 2, '.', '').' MB';
    }
}
