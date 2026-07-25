<?php

namespace App\Services\Expertis;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpertisImportTemplateService
{
    public function gestiones(): StreamedResponse
    {
        return $this->download(
            'plantilla_gestiones_expertis.xlsx',
            [
                'Canal Gestión',
                'Canal Asignación',
                'DNI',
                'Nombre Cliente',
                'Cartera',
                'Asesor',
                'Equipo',
                'Teléfono',
                'Fecha Llamada',
                'Campaña',
                'Hora',
                'Nivel 1',
                'Nivel 2',
                'Fecha Compromiso',
                'Monto',
                'Observación',
                'Medio Gestión',
            ],
            [
                'EXPERTIS',
                'ESCALL',
                '47752785',
                'CLIENTE DE EJEMPLO',
                'QAPAQ',
                'ASESOR DE EJEMPLO',
                'EQUIPO 1',
                '980617080',
                '2026-02-03',
                'APLICATIVO2',
                '07:53:17',
                'CONTACTO EFECTIVO',
                'PPM',
                '2026-02-28',
                500,
                'Observación de ejemplo',
                'MANUAL',
            ],
        );
    }

    public function pagos(): StreamedResponse
    {
        return $this->download(
            'plantilla_pagos_expertis.xlsx',
            [
                'FECHA',
                'CUENTA',
                'MONTO',
                'EJECUTIVO',
                'TIPO DE ACUERDO',
                'RECAUDO',
            ],
            [
                '2026-02-03',
                '47780017-LOS ANDES',
                277,
                'ROBERTO HUAMONTE',
                'PPM',
                '-',
            ],
        );
    }

    private function download(
        string $nombre,
        array $encabezados,
        array $ejemplo,
    ): StreamedResponse {
        return response()->streamDownload(function () use (
            $encabezados,
            $ejemplo,
        ): void {
            $temporal = tempnam(sys_get_temp_dir(), 'expertis_template_');
            if ($temporal === false) {
                throw new RuntimeException('No se pudo preparar la plantilla XLSX.');
            }

            $rutaXlsx = $temporal.'.xlsx';

            try {
                if (! rename($temporal, $rutaXlsx)) {
                    throw new RuntimeException('No se pudo preparar la plantilla XLSX.');
                }

                $writer = new Writer;
                $writer->openToFile($rutaXlsx);
                $writer->addRow(Row::fromValues($encabezados));
                $writer->addRow(Row::fromValues($ejemplo));
                $writer->close();

                readfile($rutaXlsx);
            } finally {
                if (is_file($rutaXlsx)) {
                    unlink($rutaXlsx);
                }

                if (is_file($temporal)) {
                    unlink($temporal);
                }
            }
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
