<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipificaciones_expertis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('tipificacion', 30)->unique();
            $table->string('gestion', 30)->nullable()->index();
            $table->unsignedSmallInteger('peso')->index();
            $table->text('observacion')->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipificaciones_expertis');
    }
};
