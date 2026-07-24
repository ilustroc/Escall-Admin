<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarGestionesExpertisRequest extends FormRequest
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
            'codigo' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'asesor' => ['nullable', 'string', 'max:150'],
            'equipo' => ['nullable', 'string', 'max:150'],
            'nivel_1' => ['nullable', 'string', 'max:120'],
            'nivel_2' => ['nullable', 'string', 'max:60'],
            'peso' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'in:PAGO,NO PAGO'],
            'unico' => ['nullable', 'in:0,1'],
            'campania' => ['nullable', 'string', 'max:191'],
            'medio_gestion' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100,250'],
            'formato' => ['nullable', 'in:xlsx,csv'],
        ];
    }
}
