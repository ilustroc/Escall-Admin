<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    public function test_login_valido_normaliza_el_correo_y_regenera_la_sesion(): void
    {
        $user = $this->user();
        $session = $this->app['session'];
        $session->start();
        $sessionIdAntes = $session->getId();

        $this->post('/login', [
            'email' => mb_strtoupper($user->email, 'UTF-8'),
            'password' => 'secret123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionIdAntes, $this->app['session']->getId());
    }

    public function test_login_invalido_usa_un_mensaje_generico(): void
    {
        $user = $this->user();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'incorrecta',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);

        $this->assertGuest();
    }

    public function test_login_bloquea_la_combinacion_de_correo_e_ip_despues_de_cinco_fallos(): void
    {
        $user = $this->user();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertSessionHasErrors(['email' => 'Credenciales inválidas.']);

        $this->assertGuest();
        $this->assertSame(5, RateLimiter::attempts(
            $this->credentialThrottleKey($user->email, '127.0.0.1'),
        ));
    }

    public function test_login_valido_limpia_el_contador_de_fallos(): void
    {
        $user = $this->user();

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/');
        $this->assertSame(0, RateLimiter::attempts(
            $this->credentialThrottleKey($user->email, '127.0.0.1'),
        ));

        $this->post('/logout')->assertRedirect('/login');

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalida_la_sesion_y_regenera_el_token_csrf(): void
    {
        $user = $this->user();
        $session = $this->app['session'];
        $session->start();
        $session->put('marca_de_sesion', 'activa');
        $tokenAntes = $session->token();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing('marca_de_sesion');

        $this->assertGuest();
        $this->assertNotSame($tokenAntes, $this->app['session']->token());
    }

    public function test_login_no_expone_funcionalidad_recordar_sesion(): void
    {
        $user = $this->user();

        $this->get('/login')->assertDontSee('Recordar sesión');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'remember' => true,
        ]);

        $response->assertRedirect('/');
        $this->assertNull($user->fresh()->remember_token);
        $this->assertFalse(collect($response->headers->getCookies())
            ->contains(fn ($cookie) => str_starts_with(
                $cookie->getName(),
                'remember_web_',
            )));
    }

    public function test_login_rechaza_email_con_crlf(): void
    {
        $user = $this->user();

        $this->post('/login', [
            'email' => $user->email."\r\nBcc: atacante@example.test",
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_tiene_un_limite_general_por_ip(): void
    {
        config(['security.login.ip_max_attempts_per_minute' => 2]);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        foreach (['primero@example.test', 'segundo@example.test'] as $email) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'tercero@example.test',
            'password' => 'incorrecta',
        ])
            ->assertTooManyRequests()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_respuestas_incluyen_headers_de_seguridad_y_hsts_solo_en_https(): void
    {
        $response = $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()',
            )
            ->assertHeaderMissing('Strict-Transport-Security')
            ->assertHeaderMissing('X-Powered-By');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression(
            "/script-src 'self' 'nonce-([A-Za-z0-9]+)'/",
            $csp,
        );
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);

        $this->get('https://escall.test/login')
            ->assertHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
    }

    private function credentialThrottleKey(string $email, string $ip): string
    {
        return 'login:'.hash(
            'sha256',
            mb_strtolower(trim($email), 'UTF-8').'|'.$ip,
        );
    }

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Administrador de seguridad',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('secret123'),
        ]);
    }
}
