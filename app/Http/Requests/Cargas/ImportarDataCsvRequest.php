<?php

namespace App\Http\Requests\Cargas;

use Illuminate\Foundation\Http\FormRequest;

class ImportarDataCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'csv' => [
                'required',
                'file',
                'mimetypes:text/plain,text/csv,application/csv,application/octet-stream',
                'max:204800',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'csv.required' => 'Selecciona un archivo CSV.',
            'csv.file' => 'El archivo no es válido.',
            'csv.mimetypes' => 'El archivo debe ser un CSV.',
            'csv.max' => 'El archivo excede el máximo permitido de 200 MB.',
        ];
    }
}
