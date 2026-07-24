<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'not_regex:/[\r\n]/', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.not_regex' => 'El correo electrónico contiene caracteres no permitidos.',
            'password.required' => 'Ingresa tu contraseña.',
        ];
    }

    public function credentials(): array
    {
        return $this->safe()->only(['email', 'password']);
    }
}
