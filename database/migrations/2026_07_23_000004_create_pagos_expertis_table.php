<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_expertis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('importacion_expertis_id')->nullable()
                ->constrained('importaciones_expertis')->nullOnDelete();
            $table->date('fecha')->index();
            $table->string('cuenta', 100)->index();
            $table->string('cuenta_normalizada', 100)->index();
            $table->string('dni', 20)->nullable()->index();
            $table->decimal('monto', 14, 2);
            $table->string('ejecutivo', 150)->nullable()->index();
            $table->string('tipo_acuerdo', 100)->nullable()->index();
            $table->string('recaudo', 50)->nullable()->index();
            $table->char('hash_fila', 64)->unique();
            $table->unsignedInteger('numero_fila_origen')->nullable();
            $table->timestamps();

            $table->index(
                ['cuenta_normalizada', 'fecha'],
                'pag_exp_cuenta_fecha_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_expertis');
    }
};
