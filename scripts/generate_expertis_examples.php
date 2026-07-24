<?php

declare(strict_types=1);

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

require dirname(__DIR__).'/vendor/autoload.php';

$directory = dirname(__DIR__).'/storage/app/examples';
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}

$write = static function (string $path, array $rows): void {
    $writer = new Writer();
    $writer->openToFile($path);
    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }
    $writer->close();
};

$write($directory.'/gestiones_expertis_ejemplo.xlsx', [
    [
        'Canal Gestión', 'Canal Asignación', 'DNI', 'Nombre Cliente', 'Cartera',
        'Asesor', 'Equipo', 'Teléfono', 'Fecha Llamada', 'Campaña', 'Hora',
        'Nivel 1', 'Nivel 2', 'Fecha Compromiso', 'Monto', 'Observación',
        'Medio Gestión',
    ],
    [
        'CALL', 'DIGITAL', '00123456', 'CLIENTE FICTICIO UNO', 'OH', 'ANA PEREZ',
        'EQUIPO NORTE', '999111222', '20/07/2026', 'JULIO 2026', '09:15:00',
        'CONTACTO', 'PPC', '25/07/2026', 'S/ 250.00',
        'CLIENTE ACEPTA FECHA DE SEGUIMIENTO', 'LLAMADA',
    ],
    [
        'CALL', 'DIGITAL', '08765432', 'CLIENTE FICTICIO DOS', 'KP', 'LUIS ROJAS',
        'EQUIPO SUR', '988111333', '21/07/2026', 'JULIO 2026', '11:30:00',
        'CONTACTO', 'VLL', '', 0,
        'MONTO TOTAL NEGOCIADO: S/. 1.350,50 / DOS CUOTAS', 'WHATSAPP',
    ],
    [
        'CALL', 'CAMPO', '04445555', 'CLIENTE FICTICIO TRES', 'TEC', 'MARIA DIAZ',
        'EQUIPO CENTRO', '977222444', '21/07/2026', 'JULIO 2026', '15:00:00',
        'NO CONTACTO', 'NOC', '', 0, 'SIN RESPUESTA', 'LLAMADA',
    ],
]);

$write($directory.'/pagos_expertis_ejemplo.xlsx', [
    ['FECHA', 'CUENTA', 'MONTO', 'EJECUTIVO', 'TIPO DE ACUERDO', 'RECAUDO'],
    ['22/07/2026', '00123456-OH', 'S/ 250.00', 'ANA PEREZ', 'CUOTA', 'BANCO'],
    ['23/07/2026', '08765432-KP', '1.350,50', 'LUIS ROJAS', 'CANCELACION', 'TRANSFERENCIA'],
    ['23/07/2026', '04445555-TEC', 180.75, 'MARIA DIAZ', 'PAGO PARCIAL', 'AGENTE'],
]);

echo "Archivos Expertis de ejemplo generados.\n";
