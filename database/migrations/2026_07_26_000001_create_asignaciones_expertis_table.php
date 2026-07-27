<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_expertis', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('importacion_expertis_id')->nullable()
                ->constrained('importaciones_expertis')->nullOnDelete();
            $table->char('periodo', 6);
            $table->string('empresa', 50);
            $table->string('dni', 20);
            $table->string('titular', 255);
            $table->string('codigo', 150);
            $table->string('codigo_normalizado', 150);
            $table->string('tipo_cartera', 100);
            $table->string('cosecha', 150)->nullable();
            $table->string('sub_cosecha', 150)->nullable();
            $table->string('producto', 100)->nullable();
            $table->string('sub_producto', 100)->nullable();
            $table->string('historico', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->decimal('deuda_total', 14, 2)->nullable();
            $table->decimal('deuda_capital', 14, 2)->nullable();
            $table->decimal('campania', 14, 2)->nullable();
            $table->decimal('porcentaje', 10, 4)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->unsignedSmallInteger('edad')->nullable();
            $table->unsignedSmallInteger('entidades')->nullable();
            $table->string('negocio', 100)->nullable();
            $table->decimal('sueldo', 14, 2)->nullable();
            $table->string('situacion_laboral', 100)->nullable();
            $table->unsignedSmallInteger('anio_laboral')->nullable();
            $table->string('sexo', 10)->nullable();
            $table->string('rango_sueldo', 100)->nullable();
            $table->unsignedSmallInteger('anio_castigo')->nullable();
            $table->char('hash_fila', 64);
            $table->unsignedInteger('numero_fila_origen')->nullable();
            $table->timestamps();

            $table->unique(
                ['periodo', 'empresa', 'codigo_normalizado'],
                'asig_exp_periodo_empresa_codigo_unique',
            );
            $table->index(
                ['codigo_normalizado', 'periodo'],
                'asig_exp_codigo_periodo_index',
            );
            $table->index(['dni', 'periodo'], 'asig_exp_dni_periodo_index');
            $table->index(
                ['periodo', 'tipo_cartera'],
                'asig_exp_periodo_cartera_index',
            );
            $table->index(
                ['periodo', 'departamento'],
                'asig_exp_periodo_departamento_index',
            );
            $table->index('hash_fila', 'asig_exp_hash_index');
            $table->index(
                'importacion_expertis_id',
                'asig_exp_importacion_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_expertis');
    }
};
