<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->app->environment('testing') || config('database.default') !== 'sqlite') {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $this->createLegacyTables();

        $migrations = array_merge(
            glob(database_path('migrations/2026_07_23_*_expertis_table.php')),
            glob(database_path('migrations/2026_07_26_*_expertis_table.php')),
            glob(database_path('migrations/2026_07_27_*.php')),
            glob(database_path('migrations/2026_07_29_*.php')),
        );
        sort($migrations);

        foreach ($migrations as $path) {
            $migration = require $path;
            $migration->up();
        }
    }

    private function createLegacyTables(): void
    {
        Schema::create('data', function (Blueprint $table): void {
            $table->string('codigo')->primary();
            $table->string('dni')->index();
            $table->string('titular')->nullable();
            $table->string('cartera')->nullable();
            $table->string('entidad')->nullable();
            $table->string('cosecha')->nullable();
            $table->string('sub_cartera')->nullable();
            $table->string('producto')->nullable();
            $table->string('sub_producto')->nullable();
            $table->string('historico')->nullable();
            $table->string('departamento')->nullable();
            $table->decimal('deuda_total', 14, 2)->nullable();
            $table->decimal('deuda_capital', 14, 2)->nullable();
            $table->decimal('campania', 14, 2)->nullable();
            $table->decimal('porcentaje', 12, 9)->nullable();
            $table->timestamps();
        });

        Schema::create('gestiones', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('fecha_gestion')->index();
            $table->string('dni')->index();
            $table->string('telefono')->nullable();
            $table->string('status')->nullable();
            $table->string('tipificacion')->nullable();
            $table->text('observacion')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->integer('monto_pago')->nullable();
            $table->string('nombre')->nullable();
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->index();
            $table->string('asesor')->nullable();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('operacion');
            $table->string('dni')->nullable()->index();
            $table->string('nombre')->nullable();
            $table->string('cartera')->nullable();
            $table->string('entidad')->nullable();
            $table->string('cosecha')->nullable();
            $table->string('departamento')->nullable();
            $table->string('rango')->nullable();
            $table->decimal('capital', 12, 2)->nullable();
            $table->string('producto')->nullable();
            $table->timestamps();
        });

        Schema::create('tipificaciones_impulsego', function (Blueprint $table): void {
            $table->id();
            $table->string('tipificacion')->nullable();
            $table->string('accion')->nullable();
            $table->string('contacto')->nullable();
        });

        Schema::create('tipificaciones_kpinvest', function (Blueprint $table): void {
            $table->id();
            $table->string('tipificacion')->nullable();
            $table->string('tipificacion_kpinvest')->nullable();
            $table->string('estado')->nullable();
        });

        Schema::create('tipificaciones_teccenter', function (Blueprint $table): void {
            $table->id();
            $table->string('tipificacion')->nullable();
            $table->string('accion')->nullable();
            $table->string('contacto')->nullable();
        });
    }
}
