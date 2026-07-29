<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->eliminarDatosDeAsignaciones();

        Schema::table('asignaciones_expertis', function (Blueprint $table): void {
            $table->dropIndex('asig_exp_dni_periodo_index');
        });
        Schema::table('asignaciones_expertis', function (Blueprint $table): void {
            $table->renameColumn('dni', 'documento');
        });
        Schema::table('asignaciones_expertis', function (Blueprint $table): void {
            $table->index(
                ['documento', 'periodo'],
                'asig_exp_documento_periodo_index',
            );
        });
    }

    public function down(): void
    {
        $this->eliminarDatosDeAsignaciones();

        Schema::table('asignaciones_expertis', function (Blueprint $table): void {
            $table->dropIndex('asig_exp_documento_periodo_index');
        });
        Schema::table('asignaciones_expertis', function (Blueprint $table): void {
            $table->renameColumn('documento', 'dni');
        });
        Schema::table('asignaciones_expertis', function (Blueprint $table): void {
            $table->index(['dni', 'periodo'], 'asig_exp_dni_periodo_index');
        });
    }

    private function eliminarDatosDeAsignaciones(): void
    {
        $this->eliminarJobsDeAsignaciones();
        DB::table('asignaciones_expertis')->delete();

        $importaciones = DB::table('importaciones_expertis')
            ->where('tipo', 'asignaciones')
            ->pluck('id');

        if ($importaciones->isEmpty()) {
            return;
        }

        DB::table('errores_importacion_expertis')
            ->whereIn('importacion_expertis_id', $importaciones)
            ->delete();
        DB::table('importaciones_expertis')
            ->whereIn('id', $importaciones)
            ->delete();
    }

    private function eliminarJobsDeAsignaciones(): void
    {
        foreach (['jobs', 'failed_jobs'] as $tabla) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }

            DB::table($tabla)
                ->where(function ($query): void {
                    $query
                        ->where(
                            'payload',
                            'like',
                            '%PrepararAsignacionExpertisImport%',
                        )
                        ->orWhere(
                            'payload',
                            'like',
                            '%ProcesarAsignacionExpertisImport%',
                        );
                })
                ->delete();
        }
    }
};
