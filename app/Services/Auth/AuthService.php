<?php

namespace App\Services\Auth;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function attempt(array $credentials, Request $request): bool
    {
        $throttleKey = $this->throttleKey($credentials['email'] ?? '', $request);
        $maxAttempts = (int) config('security.login.max_attempts', 5);

        if ($this->limiter->tooManyAttempts($throttleKey, $maxAttempts)) {
            return false;
        }

        if (! Auth::attempt($credentials)) {
            $this->limiter->hit(
                $throttleKey,
                (int) config('security.login.decay_seconds', 900),
            );

            return false;
        }

        $this->limiter->clear($throttleKey);
        $request->session()->regenerate();

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function throttleKey(string $email, Request $request): string
    {
        return 'login:'.hash(
            'sha256',
            mb_strtolower(trim($email), 'UTF-8').'|'.$request->ip(),
        );
    }
}
