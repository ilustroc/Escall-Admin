<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnifiedInertiaArchitectureTest extends TestCase
{
    public function test_login_is_an_inertia_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_authenticated_user_visiting_login_returns_to_dashboard(): void
    {
        $this->actingAs($this->user())
            ->get('/login')
            ->assertRedirect('/');
    }

    #[DataProvider('screenRoutes')]
    public function test_authenticated_screens_are_inertia_pages(string $url, string $component): void
    {
        $this->actingAs($this->user())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    #[DataProvider('protectedRoutes')]
    public function test_screens_redirect_guests_to_login(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    public function test_no_screen_controller_or_route_returns_a_blade_view(): void
    {
        $files = array_merge(
            glob(app_path('Http/Controllers/**/*.php')) ?: [],
            glob(app_path('Http/Controllers/*.php')) ?: [],
            glob(base_path('routes/*.php')) ?: [],
        );
        $source = collect($files)->map(fn (string $file) => file_get_contents($file))->implode("\n");

        $this->assertStringNotContainsString('return view(', $source);
        $this->assertStringNotContainsString('=> view(', $source);
    }

    public function test_only_inertia_root_is_an_administrative_blade_view(): void
    {
        $bladeFiles = collect(glob(resource_path('views/**/*.blade.php')) ?: [])
            ->merge(glob(resource_path('views/*.blade.php')) ?: [])
            ->map(fn (string $file) => str_replace('\\', '/', $file))
            ->reject(fn (string $file) => str_contains($file, '/emails/'))
            ->reject(fn (string $file) => str_contains($file, '/vendor/'))
            ->values();

        $this->assertSame(
            [str_replace('\\', '/', resource_path('views/app.blade.php'))],
            $bladeFiles->all(),
        );
    }

    public function test_app_javascript_has_no_legacy_fallback(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('createInertiaApp({', $source);
        $this->assertStringNotContainsString('querySelector', $source);
        $this->assertStringNotContainsString('getElementById', $source);
        $this->assertStringNotContainsString('DOMContentLoaded', $source);
        $this->assertStringNotContainsString('inertiaRoot', $source);
    }

    public static function screenRoutes(): array
    {
        return [
            'dashboard' => ['/', 'Dashboard/Index'],
            'cargas' => ['/cargas', 'Cargas/GestionesSp/Index'],
            'cargas data compatible' => ['/cargas?tab=data', 'Cargas/Data/Index'],
            'cargas pagos compatible' => ['/cargas?tab=pagos', 'Cargas/Pagos/Index'],
            'sp' => ['/cargas/sp', 'Cargas/GestionesSp/Index'],
            'data' => ['/cargas/data', 'Cargas/Data/Index'],
            'pagos legacy' => ['/cargas/pagos', 'Cargas/Pagos/Index'],
            'listas' => ['/listas', 'Listas/Index'],
            'tablas gestiones' => ['/tablas?tab=gestiones', 'Tablas/Index'],
            'tablas pagos' => ['/tablas?tab=pagos', 'Tablas/Index'],
            'tablas data' => ['/tablas?tab=data', 'Tablas/Index'],
            'reportes' => ['/reportes', 'Reportes/Index'],
            'impulse' => ['/reportes/impulse', 'Reportes/Impulse'],
            'kp invest' => ['/reportes/kp-invest', 'Reportes/KpInvest'],
            'carteras' => ['/reportes/carteras', 'Reportes/Carteras'],
            'expertis dashboard' => ['/expertis', 'Expertis/Dashboard'],
            'expertis importaciones' => ['/expertis/importaciones', 'Expertis/Importaciones/Index'],
            'expertis asignaciones carga' => ['/expertis/importaciones/asignaciones', 'Expertis/Importaciones/Asignaciones'],
            'expertis gestiones carga' => ['/expertis/importaciones/gestiones', 'Expertis/Importaciones/Gestiones'],
            'expertis pagos carga' => ['/expertis/importaciones/pagos', 'Expertis/Importaciones/Pagos'],
            'expertis asignaciones' => ['/expertis/asignaciones', 'Expertis/Asignaciones/Index'],
            'expertis gestiones' => ['/expertis/gestiones', 'Expertis/Gestiones/Index'],
            'expertis pagos' => ['/expertis/pagos', 'Expertis/Pagos/Index'],
            'expertis tipificaciones' => ['/expertis/tipificaciones', 'Expertis/Tipificaciones/Index'],
            'expertis reportes' => ['/expertis/reportes', 'Expertis/Reportes/Index'],
        ];
    }

    public static function protectedRoutes(): array
    {
        return array_map(
            fn (array $case) => [$case[0]],
            self::screenRoutes(),
        );
    }

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);
    }
}
