<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarAsignacionExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'importacion_id' => [
                'required',
                'integer',
                'exists:importaciones_expertis,id',
            ],
        ];
    }
}
