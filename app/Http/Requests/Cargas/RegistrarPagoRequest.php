<?php

namespace App\Http\Requests\Cargas;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:30'],
            'asesor' => ['nullable', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
            'operacion' => ['required', 'in:CANCELACION,PAGO PARCIAL,CUOTA'],
            'dni' => ['nullable', 'string', 'max:15'],
            'nombre' => ['nullable', 'string', 'max:150'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'entidad' => ['nullable', 'string', 'max:100'],
            'cosecha' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'rango' => ['nullable', 'string', 'max:30'],
            'capital' => ['nullable', 'numeric'],
            'producto' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Busca o ingresa el código de la cuenta.',
            'fecha.required' => 'Selecciona la fecha del pago.',
            'monto.required' => 'Ingresa el monto del pago.',
            'monto.min' => 'El monto no puede ser negativo.',
            'operacion.in' => 'Selecciona una operación válida.',
        ];
    }
}
