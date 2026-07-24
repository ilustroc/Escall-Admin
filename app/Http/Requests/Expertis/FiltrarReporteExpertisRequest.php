<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarReporteExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['nullable', 'in:gestiones,pagos'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'asesor' => ['nullable', 'string', 'max:150'],
            'equipo' => ['nullable', 'string', 'max:150'],
            'nivel_1' => ['nullable', 'string', 'max:120'],
            'nivel_2' => ['nullable', 'string', 'max:60'],
            'estado' => ['nullable', 'in:PAGO,NO PAGO'],
            'unico' => ['nullable', 'in:0,1'],
            'campania' => ['nullable', 'string', 'max:191'],
            'medio_gestion' => ['nullable', 'string', 'max:100'],
            'cuenta' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20'],
            'ejecutivo' => ['nullable', 'string', 'max:150'],
            'tipo_acuerdo' => ['nullable', 'string', 'max:100'],
            'recaudo' => ['nullable', 'string', 'max:50'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => ['nullable', 'numeric', 'gte:monto_min'],
            'formato' => ['nullable', 'in:xlsx,csv'],
        ];
    }
}
