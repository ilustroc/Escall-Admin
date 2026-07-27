<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarAsignacionesExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'regex:/^\d{6}$/'],
            'dni' => ['nullable', 'string', 'max:20'],
            'codigo' => ['nullable', 'string', 'max:150'],
            'titular' => ['nullable', 'string', 'max:255'],
            'tipo_cartera' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'producto' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:50,75,100'],
        ];
    }
}
