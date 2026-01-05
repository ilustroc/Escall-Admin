<?php
// database/migrations/2026_01_05_000000_create_pagos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // Datos de captura
            $table->string('codigo', 30)->index();         // NUM CUENTA (de 'data.codigo')
            $table->string('asesor', 100)->nullable();     // asesor que registra
            $table->date('fecha');                         // fecha de pago
            $table->decimal('monto', 12, 2);               // monto de pago
            $table->string('operacion', 20);               // CANCELACION / PAGO PARCIAL / CUOTA

            // Snapshot desde DATA/ASIGNACIÓN en el momento del registro
            $table->string('dni', 15)->nullable()->index();
            $table->string('nombre', 150)->nullable();
            $table->string('cartera', 100)->nullable();
            $table->string('entidad', 100)->nullable();
            $table->string('cosecha', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('rango', 30)->nullable();
            $table->decimal('capital', 12, 2)->nullable();
            $table->string('producto', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pagos');
    }
};
