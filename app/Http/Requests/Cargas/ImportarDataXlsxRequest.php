<?php

namespace App\Http\Requests\Cargas;

use Illuminate\Foundation\Http\FormRequest;

class ImportarDataXlsxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'mimes:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream',
                'max:102400',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona un archivo XLSX.',
            'archivo.file' => 'El archivo no es válido.',
            'archivo.mimes' => 'El archivo debe ser un Excel .xlsx.',
            'archivo.mimetypes' => 'El contenido no corresponde a un archivo XLSX válido.',
            'archivo.max' => 'El archivo excede el máximo permitido de 100 MB.',
        ];
    }
}
