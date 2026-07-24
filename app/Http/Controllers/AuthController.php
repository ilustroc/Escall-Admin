<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function doLogin(LoginRequest $request, AuthService $auth): RedirectResponse
    {
        if ($auth->attempt($request->credentials(), $request->boolean('remember'), $request)) {
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Credenciales inválidas.'])
            ->onlyInput('email');
    }

    public function logout(Request $request, AuthService $auth): RedirectResponse
    {
        $auth->logout($request);

        return redirect()->route('login');
    }
}
