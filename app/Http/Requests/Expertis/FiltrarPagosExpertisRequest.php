<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarPagosExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'cuenta' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20'],
            'ejecutivo' => ['nullable', 'string', 'max:150'],
            'tipo_acuerdo' => ['nullable', 'string', 'max:100'],
            'recaudo' => ['nullable', 'string', 'max:50'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => ['nullable', 'numeric', 'gte:monto_min'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100,250'],
            'formato' => ['nullable', 'in:xlsx,csv'],
        ];
    }
}
