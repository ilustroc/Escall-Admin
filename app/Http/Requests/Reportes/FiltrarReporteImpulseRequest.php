<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarReporteImpulseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $today = now()->toDateString();
        $this->merge([
            'fi' => $this->query('fi', $today),
            'ff' => $this->query('ff', $this->query('fi', $today)),
            'equipo' => $this->query('equipo', 2),
        ]);
    }

    public function rules(): array
    {
        return [
            'fi' => ['required', 'date'],
            'ff' => ['required', 'date', 'after_or_equal:fi'],
            'equipo' => ['nullable', 'integer', 'in:2,3'],
        ];
    }
}
