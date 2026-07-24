<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'app' => [
                'name' => config('app.name', 'ESCALL Perú'),
                'environment' => app()->environment(),
            ],
            'auth' => [
                'user' => fn () => $request->user()
                    ? $request->user()->only('id', 'name', 'email')
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success')
                    ?? $request->session()->get('ok'),
                'warning' => fn () => $request->session()->get('warning')
                    ?? $request->session()->get('warn'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'permissions' => [],
        ]);
    }
}
