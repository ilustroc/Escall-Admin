<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarReporteKpInvestRequest extends FormRequest
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
        ]);
    }

    public function rules(): array
    {
        return [
            'fi' => ['required', 'date'],
            'ff' => ['required', 'date', 'after_or_equal:fi'],
        ];
    }
}
