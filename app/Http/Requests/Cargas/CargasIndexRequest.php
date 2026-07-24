<?php

namespace App\Http\Requests\Cargas;

use Illuminate\Foundation\Http\FormRequest;

class CargasIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tab' => $this->query('tab', 'gestiones')]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['required', 'in:gestiones,data,pagos'],
        ];
    }
}
