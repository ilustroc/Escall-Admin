<?php

namespace App\Http\Requests\Cargas;

use Illuminate\Foundation\Http\FormRequest;

class BuscarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => trim((string) $this->query('codigo', ''))]);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return ['codigo.required' => 'Ingresa un código para buscar.'];
    }
}
