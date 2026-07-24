<?php

namespace App\Http\Requests\Cargas;

use Illuminate\Foundation\Http\FormRequest;

class PreviewGestionesSpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fi' => ['required', 'date'],
            'ff' => ['required', 'date', 'after_or_equal:fi'],
        ];
    }

    public function messages(): array
    {
        return [
            'fi.required' => 'Selecciona la fecha inicial.',
            'ff.required' => 'Selecciona la fecha final.',
            'ff.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function range(): array
    {
        return [$this->validated('fi'), $this->validated('ff')];
    }
}
