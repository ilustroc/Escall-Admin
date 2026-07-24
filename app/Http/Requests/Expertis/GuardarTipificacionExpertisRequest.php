<?php

namespace App\Http\Requests\Expertis;

use App\Models\TipificacionExpertis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarTipificacionExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipificacion' => mb_strtoupper(trim((string) $this->input('tipificacion')), 'UTF-8'),
            'gestion' => $this->filled('gestion')
                ? mb_strtoupper(trim((string) $this->input('gestion')), 'UTF-8')
                : null,
            'activo' => $this->boolean('activo', true),
        ]);
    }

    public function rules(): array
    {
        /** @var TipificacionExpertis|null $tipificacion */
        $tipificacion = $this->route('tipificacion');

        return [
            'tipificacion' => [
                'required',
                'string',
                'max:30',
                Rule::unique('tipificaciones_expertis', 'tipificacion')
                    ->ignore($tipificacion?->getKey()),
            ],
            'gestion' => ['nullable', 'string', 'max:30'],
            'peso' => ['required', 'integer', 'min:1', 'max:65535'],
            'observacion' => ['nullable', 'string', 'max:5000'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
