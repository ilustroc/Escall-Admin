<?php

namespace App\Http\Requests\Listas;

use Illuminate\Foundation\Http\FormRequest;

class EliminarListaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required'],
            'type' => ['required', 'in:pago,gestion,data'],
        ];
    }
}
