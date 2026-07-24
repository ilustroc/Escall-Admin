<?php

namespace App\Http\Requests\Expertis;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarImportacionExpertisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string', 'size:40', 'alpha_num'],
        ];
    }
}
