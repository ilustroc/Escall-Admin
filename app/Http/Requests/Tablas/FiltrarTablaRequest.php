<?php

namespace App\Http\Requests\Tablas;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarTablaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tab' => $this->query('tab', 'gestiones'),
            'mes' => $this->query('mes', now()->format('Y-m')),
            'week' => $this->query('week', now()->format('o-\WW')),
            'per_page' => $this->query('per_page', 25),
            'direction' => $this->query('direction', 'desc'),
        ]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['required', 'in:gestiones,pagos,data'],
            'mes' => ['required', 'date_format:Y-m'],
            'week' => ['required', 'regex:/^\d{4}-W\d{2}$/'],
            'search' => ['nullable', 'string', 'max:150'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['required', 'in:asc,desc'],
            'per_page' => ['required', 'integer', 'in:25,50,100,250'],
        ];
    }
}
