<?php

namespace App\Http\Requests\Listas;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarListaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tab' => $this->query('tab', 'pagos'),
            'per_page' => $this->query('per_page', 25),
            'direction' => $this->query('direction', 'desc'),
        ]);
    }

    public function rules(): array
    {
        return [
            'tab' => ['required', 'in:pagos,gestiones,data'],
            'dni' => ['nullable', 'string', 'max:30'],
            'fecha_ini' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_ini'],
            'cosecha' => ['nullable', 'string', 'max:100'],
            'cartera' => ['nullable', 'string', 'max:100'],
            'resultado' => ['nullable', 'string', 'max:120'],
            'asesor' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['required', 'in:asc,desc'],
            'per_page' => ['required', 'integer', 'in:25,50,100,250'],
        ];
    }
}
