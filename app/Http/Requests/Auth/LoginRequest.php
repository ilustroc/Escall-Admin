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
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (
            ! is_string($email)
            || str_contains($email, "\r")
            || str_contains($email, "\n")
        ) {
            return;
        }

        $this->merge([
            'email' => mb_strtolower(trim($email), 'UTF-8'),
        ]);
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
