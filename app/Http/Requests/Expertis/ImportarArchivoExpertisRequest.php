<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;
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
                'required',
                'file',
                'mimes:xlsx',
                'max:'.(config('expertis.import_max_mb', 50) * 1024),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona un archivo XLSX.',
            'archivo.mimes' => 'El archivo debe ser un XLSX válido.',
            'archivo.max' => 'El archivo excede el tamaño máximo permitido.',
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
}
