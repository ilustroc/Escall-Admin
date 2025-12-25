{{-- resources/views/auth/login.blade.php --}}
@php
  $appName = config('app.name', 'Sistema');
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Iniciar sesión | {{ $appName }}</title>

  {{-- Tailwind CDN (sin Vite, sin build) --}}
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">
  <div class="relative min-h-screen overflow-hidden">
    {{-- Fondo suave --}}
    <div class="pointer-events-none absolute inset-0">
      <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-indigo-200/40 blur-3xl"></div>
      <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-slate-200/70 blur-3xl"></div>
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(15,23,42,0.08)_1px,transparent_0)] [background-size:18px_18px] opacity-30"></div>
    </div>

    <div class="relative flex min-h-screen items-center justify-center p-4">
      <div class="w-full max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur">
          <div class="p-6 sm:p-7 space-y-5">
            {{-- Header --}}
            <div class="space-y-1">
              <h1 class="text-lg font-semibold leading-tight">Iniciar sesión</h1>
            </div>

            {{-- Mensajes --}}
            @if(session('ok'))
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                {{ session('ok') }}
              </div>
            @endif

            @if($errors->any())
              <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                <div class="font-semibold mb-1">Revisa lo siguiente:</div>
                <ul class="list-disc pl-4 space-y-0.5">
                  @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login.post') }}" class="space-y-4 text-sm">
              @csrf

              {{-- Email --}}
              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700">Email</label>
                <input
                  type="email"
                  name="email"
                  value="{{ old('email') }}"
                  required
                  autocomplete="email"
                  placeholder="tucorreo@dominio.com"
                  class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm
                         focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('email')
                  <p class="text-[11px] text-slate-600">{{ $message }}</p>
                @enderror
              </div>

              {{-- Password --}}
              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700">Contraseña</label>
                <input
                  type="password"
                  name="password"
                  required
                  autocomplete="current-password"
                  placeholder="••••••••"
                  class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm
                         focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('password')
                  <p class="text-[11px] text-slate-600">{{ $message }}</p>
                @enderror
              </div>

              {{-- Remember --}}
              <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-xs text-slate-600 select-none">
                  <input
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    {{ old('remember') ? 'checked' : '' }}
                  >
                  <span>Recordarme</span>
                </label>

                {{-- Si tienes reset password, descomenta --}}
                {{-- <a href="{{ route('password.request') }}" class="text-xs text-indigo-600 hover:underline">¿Olvidaste tu contraseña?</a> --}}
              </div>

              <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2
                       text-sm font-medium text-white shadow-sm hover:bg-slate-800
                       focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-1"
              >
                Entrar
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
