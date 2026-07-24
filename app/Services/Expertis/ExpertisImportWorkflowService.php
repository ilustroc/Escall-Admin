<?php

namespace App\Services\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ExpertisImportWorkflowService
{
    public function __construct(
        private readonly ExpertisSpreadsheetService $spreadsheet,
        private readonly GestionExpertisImportService $managements,
        private readonly PagoExpertisImportService $payments,
    ) {}

    public function preview(
        UploadedFile $file,
        string $type,
        int $userId,
        Session $session,
    ): array {
        $token = Str::random(40);
        $path = $file->storeAs(
            'private/expertis/previews/'.$userId,
            $token.'.xlsx',
            'local',
        );

        try {
            $inspection = $this->spreadsheet->inspeccionar(
                Storage::disk('local')->path($path),
                $type,
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw new RuntimeException('No se pudo leer el contenido del XLSX.', 0, $exception);
        }

        $hash = hash_file('sha256', Storage::disk('local')->path($path));
        $duplicate = ImportacionExpertis::query()
            ->where('tipo', $type)
            ->where('hash_archivo', $hash)
            ->first();

        if ($inspection['columnas_faltantes'] === []) {
            $session->put('expertis_preview.'.$token, [
                'tipo' => $type,
                'user_id' => $userId,
                'ruta' => $path,
                'nombre_original' => $file->getClientOriginalName(),
                'expira' => now()->addMinutes(
                    config('expertis.preview_ttl_minutes', 120),
                )->timestamp,
            ]);
        } else {
            Storage::disk('local')->delete($path);
        }

        return array_merge($inspection, [
            'token' => $inspection['columnas_faltantes'] === [] ? $token : null,
            'archivo' => [
                'nombre' => $file->getClientOriginalName(),
                'tamano' => $file->getSize(),
                'hash' => $hash,
            ],
            'archivo_duplicado' => $duplicate ? [
                'id' => $duplicate->id,
                'estado' => $duplicate->estado,
                'fecha' => $duplicate->created_at,
            ] : null,
        ]);
    }

    public function import(
        string $token,
        string $type,
        int $userId,
        Session $session,
    ): array {
        $metadata = $session->pull('expertis_preview.'.$token);

        if (
            ! is_array($metadata)
            || ($metadata['tipo'] ?? null) !== $type
            || ($metadata['user_id'] ?? null) !== $userId
            || now()->timestamp > ($metadata['expira'] ?? 0)
            || ! Storage::disk('local')->exists($metadata['ruta'] ?? '')
        ) {
            throw new RuntimeException('La vista previa venció. Selecciona el archivo nuevamente.');
        }

        return match ($type) {
            ExpertisSpreadsheetService::GESTIONES => $this->managements->importar(
                $metadata['ruta'],
                $metadata['nombre_original'],
                $userId,
            ),
            ExpertisSpreadsheetService::PAGOS => $this->payments->importar(
                $metadata['ruta'],
                $metadata['nombre_original'],
                $userId,
            ),
            default => throw new RuntimeException('Tipo de importación Expertis no válido.'),
        };
    }
}
