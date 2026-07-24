<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarImportacionesExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['nullable', 'in:gestiones,pagos'],
            'estado' => ['nullable', 'string', 'max:40'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'in:15,25,50,100'],
        ];
    }
}
