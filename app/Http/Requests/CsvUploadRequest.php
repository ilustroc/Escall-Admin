<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CsvUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Campo: name="csv"
            'csv' => [
                'required',
                'file',
                // algunos navegadores/hostings reportan CSV como text/plain o application/csv
                'mimetypes:text/plain,text/csv,application/csv,application/octet-stream',
                // hasta 200 MB para cargas grandes
                'max:204800',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'csv.required'  => 'Debes seleccionar un archivo CSV.',
            'csv.file'      => 'El archivo no es válido.',
            'csv.mimetypes' => 'El archivo debe ser un CSV.',
            'csv.max'       => 'El archivo excede el tamaño máximo permitido (200 MB).',
        ];
    }

    public function attributes(): array
    {
        return [
            'csv' => 'archivo CSV',
        ];
    }
}
