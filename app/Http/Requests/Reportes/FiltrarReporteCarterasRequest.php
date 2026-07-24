<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarReporteCarterasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $defaultTag = $this->routeIs('reportes.carteras.index')
            ? 'OCTUBRE25'
            : 'DICIEMBRE';

        $this->merge([
            'tag' => $this->query('tag', $defaultTag),
            'lote' => $this->query('lote', '1809'),
            'mes' => $this->query('mes', now()->format('Y-m')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tag' => ['required', 'string', 'max:60'],
            'lote' => ['required', 'string', 'max:60'],
            'mes' => ['required', 'date_format:Y-m'],
        ];
    }
}
