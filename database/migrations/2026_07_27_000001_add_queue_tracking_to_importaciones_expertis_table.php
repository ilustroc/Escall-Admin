<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('importaciones_expertis', function (Blueprint $table): void {
            $table->timestamp('heartbeat_at')->nullable()->after('finalizado_at');
            $table->timestamp('queued_at')->nullable()->after('heartbeat_at');
            $table->unsignedInteger('progreso_actual')->default(0)->after('queued_at');
            $table->unsignedInteger('progreso_total')->default(0)->after('progreso_actual');
            $table->string('fase', 30)->nullable()->after('progreso_total');
            $table->unsignedSmallInteger('intentos')->default(0)->after('fase');

            $table->index(
                ['estado', 'heartbeat_at'],
                'imp_exp_estado_heartbeat_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('importaciones_expertis', function (Blueprint $table): void {
            $table->dropIndex('imp_exp_estado_heartbeat_index');
            $table->dropColumn([
                'heartbeat_at',
                'queued_at',
                'progreso_actual',
                'progreso_total',
                'fase',
                'intentos',
            ]);
        });
    }
};
