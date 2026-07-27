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
            [[
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
            ]],
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
            [[
                '2026-02-03',
                '47780017-LOS ANDES',
                277,
                'ROBERTO HUAMONTE',
                'PPM',
                '-',
            ]],
        );
    }

    public function asignaciones(): StreamedResponse
    {
        return $this->download(
            'plantilla_asignaciones_expertis.xlsx',
            ExpertisSpreadsheetService::ENCABEZADOS_ASIGNACIONES,
            [
                [
                    '202607', 'EXPERTIS', '00000001', 'CLIENTE FICTICIO UNO',
                    '00000001-LOS ANDES', 'LOS ANDES', 'COSECHA 2026', '-',
                    'S4', 'B', 'JULIO 2026', 'PUNO', 2994.71, 1190.85,
                    476.34, 0, '1999-04-25', 27, 1, 'NO', '-', '-',
                    '-', 'F', '-', 2021,
                ],
                [
                    '202607', 'EXPERTIS', '00000002', 'CLIENTE FICTICIO DOS',
                    '00000002-PRO', 'PRO', 'COSECHA 2025', 'TRAMO A',
                    'PRÉSTAMO', 'CONSUMO', '-', 'LIMA', 1500, 900,
                    '-', 12.5, '1988-10-03', 37, 2, 'SÍ', 1800,
                    'DEPENDIENTE', 2020, 'M', '1500 A 2000', 2022,
                ],
                [
                    '202607', 'EXPERTIS', '00000003', 'CLIENTE FICTICIO TRES',
                    '00000003-CREDINKA', 'CREDINKA', '-', '-', 'CRÉDITO',
                    '-', 'JULIO 2026', 'CUSCO', 830.40, 500.25, 100,
                    '3.2500%', '-', '-', 0, '-', '-', '-', '-', 'F', '-', '-',
                ],
            ],
        );
    }

    private function download(
        string $nombre,
        array $encabezados,
        array $filas,
    ): StreamedResponse {
        return response()->streamDownload(function () use (
            $encabezados,
            $filas,
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
                foreach ($filas as $fila) {
                    $writer->addRow(Row::fromValues($fila));
                }
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
