<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestiones_expertis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('importacion_expertis_id')->nullable()
                ->constrained('importaciones_expertis')->nullOnDelete();
            $table->string('codigo', 100)->index();
            $table->string('codigo_normalizado', 100)->index();
            $table->string('canal_gestion', 100)->nullable();
            $table->string('canal_asignacion', 100)->nullable();
            $table->string('dni', 20)->index();
            $table->string('nombre_cliente', 180)->nullable();
            $table->string('cartera', 100)->index();
            $table->string('asesor', 150)->nullable()->index();
            $table->string('equipo', 150)->nullable()->index();
            $table->string('telefono', 30)->nullable();
            $table->date('fecha_llamada')->index();
            $table->time('hora')->nullable();
            $table->dateTime('fecha_hora')->nullable()->index();
            $table->string('campania', 191)->nullable();
            $table->string('nivel_1', 120)->nullable()->index();
            $table->string('nivel_2', 60)->nullable()->index();
            $table->foreignId('tipificacion_expertis_id')->nullable()
                ->constrained('tipificaciones_expertis')->nullOnDelete();
            $table->date('fecha_compromiso')->nullable();
            $table->decimal('monto', 14, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->string('medio_gestion', 100)->nullable();
            $table->char('hash_fila', 64)->unique();
            $table->unsignedInteger('numero_fila_origen')->nullable();
            $table->timestamps();

            $table->index(
                ['codigo_normalizado', 'fecha_llamada'],
                'ges_exp_codigo_fecha_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestiones_expertis');
    }
};
