<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpertisAssignmentDocumentMigrationTest extends TestCase
{
    public function test_migracion_reemplaza_dni_y_descarta_asignaciones_e_historial(): void
    {
        $migration = require database_path(
            'migrations/2026_07_29_000001_replace_dni_with_documento_in_asignaciones_expertis_table.php',
        );
        $migration->down();

        $assignmentImportId = $this->createImport('asignaciones', 'assignment');
        $paymentImportId = $this->createImport('pagos', 'payment');

        DB::table('asignaciones_expertis')->insert([
            'importacion_expertis_id' => $assignmentImportId,
            'periodo' => '202607',
            'empresa' => 'EXPERTIS',
            'dni' => '00000001',
            'titular' => 'CLIENTE FICTICIO',
            'codigo' => '00000001-LOS ANDES',
            'codigo_normalizado' => '1-LOS ANDES',
            'tipo_cartera' => 'LOS ANDES',
            'hash_fila' => hash('sha256', 'assignment'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('errores_importacion_expertis')->insert([
            'importacion_expertis_id' => $assignmentImportId,
            'numero_fila' => 2,
            'campo' => 'DOCUMENTO',
            'mensaje' => 'Error ficticio.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createQueuedJob('PrepararAsignacionExpertisImport');
        $otherJobId = $this->createQueuedJob('OtroJob');
        $this->createFailedJob('ProcesarAsignacionExpertisImport', 'assignment-job');
        $otherFailedJobId = $this->createFailedJob('OtroJob', 'other-job');

        $migration->up();

        $this->assertTrue(Schema::hasColumn('asignaciones_expertis', 'documento'));
        $this->assertFalse(Schema::hasColumn('asignaciones_expertis', 'dni'));
        $this->assertDatabaseCount('asignaciones_expertis', 0);
        $this->assertDatabaseMissing('importaciones_expertis', [
            'id' => $assignmentImportId,
        ]);
        $this->assertDatabaseMissing('errores_importacion_expertis', [
            'importacion_expertis_id' => $assignmentImportId,
        ]);
        $this->assertDatabaseHas('importaciones_expertis', [
            'id' => $paymentImportId,
            'tipo' => 'pagos',
        ]);
        $this->assertDatabaseMissing('jobs', [
            'payload' => 'PrepararAsignacionExpertisImport',
        ]);
        $this->assertDatabaseHas('jobs', ['id' => $otherJobId]);
        $this->assertDatabaseMissing('failed_jobs', [
            'payload' => 'ProcesarAsignacionExpertisImport',
        ]);
        $this->assertDatabaseHas('failed_jobs', ['id' => $otherFailedJobId]);
    }

    private function createImport(string $tipo, string $suffix): int
    {
        return DB::table('importaciones_expertis')->insertGetId([
            'tipo' => $tipo,
            'nombre_original' => $suffix.'.xlsx',
            'nombre_guardado' => $suffix.'.xlsx',
            'ruta_archivo' => 'private/expertis/'.$suffix.'.xlsx',
            'hash_archivo' => hash('sha256', $suffix),
            'estado' => 'completado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createQueuedJob(string $payload): int
    {
        return DB::table('jobs')->insertGetId([
            'queue' => 'expertis',
            'payload' => $payload,
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);
    }

    private function createFailedJob(string $payload, string $uuid): int
    {
        return DB::table('failed_jobs')->insertGetId([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'expertis',
            'payload' => $payload,
            'exception' => 'Error ficticio.',
        ]);
    }
}
