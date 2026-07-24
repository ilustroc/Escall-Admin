<?php

namespace App\Services\Expertis;

use Generator;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

class ExpertisSpreadsheetService
{
    public const GESTIONES = 'gestiones';

    public const PAGOS = 'pagos';

    private const COLUMNAS_GESTIONES = [
        'canal gestion' => 'canal_gestion',
        'canal asignacion' => 'canal_asignacion',
        'dni' => 'dni',
        'nombre cliente' => 'nombre_cliente',
        'cartera' => 'cartera',
        'asesor' => 'asesor',
        'equipo' => 'equipo',
        'telefono' => 'telefono',
        'fecha llamada' => 'fecha_llamada',
        'campana' => 'campania',
        'hora' => 'hora',
        'nivel 1' => 'nivel_1',
        'nivel 2' => 'nivel_2',
        'fecha compromiso' => 'fecha_compromiso',
        'monto' => 'monto',
        'observacion' => 'observacion',
        'medio gestion' => 'medio_gestion',
    ];

    private const COLUMNAS_PAGOS = [
        'fecha' => 'fecha',
        'cuenta' => 'cuenta',
        'monto' => 'monto',
        'ejecutivo' => 'ejecutivo',
        'tipo de acuerdo' => 'tipo_acuerdo',
        'recaudo' => 'recaudo',
    ];

    private const OBLIGATORIAS = [
        self::GESTIONES => [
            'dni' => 'DNI',
            'cartera' => 'Cartera',
            'fecha_llamada' => 'Fecha Llamada',
            'nivel_2' => 'Nivel 2',
        ],
        self::PAGOS => [
            'fecha' => 'FECHA',
            'cuenta' => 'CUENTA',
            'monto' => 'MONTO',
        ],
    ];

    public function __construct(
        private readonly NormalizadorExpertisService $normalizador,
    ) {}

    public function inspeccionar(string $ruta, string $tipo, int $limite = 20): array
    {
        $reader = new Reader;
        $reader->open($ruta);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $encabezados = null;
                $mapa = [];
                $vistaPrevia = [];
                $numeroFila = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $numeroFila++;
                    $valores = $row->toArray();

                    if ($this->filaVacia($valores)) {
                        continue;
                    }

                    if ($encabezados === null) {
                        $encabezados = array_map(
                            fn ($valor) => trim((string) ($valor ?? '')),
                            $valores,
                        );
                        $mapa = $this->mapearEncabezados($encabezados, $tipo);

                        continue;
                    }

                    if (count($vistaPrevia) < $limite) {
                        $vistaPrevia[] = $this->asociar($valores, $mapa);
                    }

                    if (count($vistaPrevia) >= $limite) {
                        break;
                    }
                }

                if ($encabezados === null) {
                    throw new RuntimeException('El archivo no contiene encabezados ni filas legibles.');
                }

                $presentes = array_values(array_filter($mapa));
                $faltantes = [];
                foreach (self::OBLIGATORIAS[$tipo] as $clave => $etiqueta) {
                    if (! in_array($clave, $presentes, true)) {
                        $faltantes[] = $etiqueta;
                    }
                }

                return [
                    'encabezados' => $encabezados,
                    'columnas_detectadas' => array_values(array_unique($presentes)),
                    'columnas_faltantes' => $faltantes,
                    'preview' => $vistaPrevia,
                ];
            }
        } finally {
            $reader->close();
        }

        throw new RuntimeException('El archivo XLSX no contiene hojas.');
    }

    public function filas(string $ruta, string $tipo): Generator
    {
        $reader = new Reader;
        $reader->open($ruta);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $mapa = null;
                $numeroFila = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $numeroFila++;
                    $valores = $row->toArray();

                    if ($this->filaVacia($valores)) {
                        continue;
                    }

                    if ($mapa === null) {
                        $mapa = $this->mapearEncabezados($valores, $tipo);

                        continue;
                    }

                    yield [
                        'numero_fila' => $numeroFila,
                        'datos' => $this->asociar($valores, $mapa),
                        'originales' => array_map(
                            fn ($valor) => $valor instanceof \DateTimeInterface
                                ? $valor->format('Y-m-d H:i:s')
                                : $valor,
                            $valores,
                        ),
                    ];
                }

                return;
            }
        } finally {
            $reader->close();
        }
    }

    private function mapearEncabezados(array $encabezados, string $tipo): array
    {
        $definiciones = $tipo === self::GESTIONES
            ? self::COLUMNAS_GESTIONES
            : self::COLUMNAS_PAGOS;

        return array_map(function ($encabezado) use ($definiciones) {
            $normalizado = $this->normalizador->encabezado((string) ($encabezado ?? ''));

            return $definiciones[$normalizado] ?? null;
        }, $encabezados);
    }

    private function asociar(array $valores, array $mapa): array
    {
        $fila = [];
        foreach ($mapa as $indice => $clave) {
            if ($clave !== null) {
                $fila[$clave] = $valores[$indice] ?? null;
            }
        }

        return $fila;
    }

    private function filaVacia(array $valores): bool
    {
        foreach ($valores as $valor) {
            if ($valor !== null && trim((string) $valor) !== '') {
                return false;
            }
        }

        return true;
    }
}
