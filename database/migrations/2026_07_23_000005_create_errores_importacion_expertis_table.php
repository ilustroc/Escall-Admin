<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('errores_importacion_expertis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('importacion_expertis_id')
                ->constrained('importaciones_expertis')->cascadeOnDelete();
            $table->unsignedInteger('numero_fila');
            $table->string('campo')->nullable();
            $table->text('mensaje');
            $table->json('datos_originales')->nullable();
            $table->timestamps();

            $table->index(
                ['importacion_expertis_id', 'numero_fila'],
                'err_exp_importacion_fila_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('errores_importacion_expertis');
    }
};
