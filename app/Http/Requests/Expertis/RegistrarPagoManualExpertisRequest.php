<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoManualExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'cuenta' => [
                'required',
                'string',
                'max:100',
                'regex:/^\s*\d{1,20}\s*-\s*[^-].*$/u',
            ],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'ejecutivo' => ['nullable', 'string', 'max:150'],
            'tipo_acuerdo' => ['nullable', 'string', 'max:100'],
            'recaudo' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'cuenta.regex' => 'La cuenta debe tener el formato DNI-CARTERA, por ejemplo 47752785-QAPAQ.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'fecha' => 'fecha de pago',
            'cuenta' => 'cuenta',
            'monto' => 'monto',
            'ejecutivo' => 'ejecutivo',
            'tipo_acuerdo' => 'tipo de acuerdo',
            'recaudo' => 'recaudo',
        ];
    }
}
