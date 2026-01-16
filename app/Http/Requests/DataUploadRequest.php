<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataUploadRequest extends FormRequest
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
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file'     => 'El archivo no es válido.',
            'archivo.mimes'    => 'El archivo debe ser un Excel (.xlsx).',
            'archivo.mimetypes'=> 'El archivo debe ser un Excel (.xlsx).',
            'archivo.max'      => 'El archivo excede el tamaño máximo permitido (100 MB).',
        ];
    }

    public function attributes(): array
    {
        return [
            'archivo' => 'archivo Excel',
        ];
    }
}
