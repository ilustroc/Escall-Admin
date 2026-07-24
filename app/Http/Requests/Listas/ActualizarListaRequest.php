<?php

namespace App\Http\Requests\Listas;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarListaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required'],
            'type' => ['required', 'in:pago,gestion,data'],
            'fecha' => ['nullable', 'date'],
            'monto' => ['nullable', 'numeric', 'min:0'],
            'cosecha' => ['nullable', 'string', 'max:100'],
            'asesor' => ['nullable', 'string', 'max:150'],
            'operacion' => ['nullable', 'string', 'max:100'],
            'resultado' => ['nullable', 'string', 'max:120'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'fecha_gestion' => ['nullable', 'date'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'titular' => ['nullable', 'string', 'max:150'],
            'deuda_capital' => ['nullable', 'numeric'],
        ];
    }
}
