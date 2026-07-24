<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones_expertis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 20)->index();
            $table->string('nombre_original');
            $table->string('nombre_guardado');
            $table->string('ruta_archivo');
            $table->char('hash_archivo', 64);
            $table->string('estado', 40)->default('pendiente')->index();
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_insertadas')->default(0);
            $table->unsignedInteger('filas_actualizadas')->default(0);
            $table->unsignedInteger('filas_duplicadas')->default(0);
            $table->unsignedInteger('filas_error')->default(0);
            $table->date('fecha_minima')->nullable();
            $table->date('fecha_maxima')->nullable();
            $table->timestamp('iniciado_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->json('resumen')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->timestamps();

            $table->unique(['tipo', 'hash_archivo'], 'imp_exp_tipo_hash_unique');
            $table->index('hash_archivo', 'imp_exp_hash_index');
            $table->index('created_at', 'imp_exp_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_expertis');
    }
};
