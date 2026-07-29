<?php

namespace App\Services\Expertis;

use Generator;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

class ExpertisSpreadsheetService
{
    public const GESTIONES = 'gestiones';

    public const PAGOS = 'pagos';

    public const ASIGNACIONES = 'asignaciones';

    public const ENCABEZADOS_ASIGNACIONES = [
        'PERIODO',
        'EMPRESA',
        'DOCUMENTO',
        'TITULAR',
        'CODIGO',
        'TIPO DE CARTERA',
        'COSECHA',
        'SUB COSECHA',
        'PRODUCTO',
        'SUB_PRODUCTO',
        'HISTORICO',
        'DEPARTAMENTO',
        'DEUDA TOTAL',
        'DEUDA CAPITAL',
        'CAMPAÑA',
        '%',
        'AÑO_NACIMIENTO',
        'EDAD',
        'ENTIDADES',
        'NEGOCIO',
        'SUELDO',
        'SITUACION_LABORAL',
        'AÑO_LABORAL',
        'SEXO',
        'RANGO_SUELDO',
        'AÑO_CASTIGO',
    ];

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

    private const COLUMNAS_ASIGNACIONES = [
        'periodo' => 'periodo',
        'empresa' => 'empresa',
        'dni' => 'documento',
        'documento' => 'documento',
        'titular' => 'titular',
        'codigo' => 'codigo',
        'tipo de cartera' => 'tipo_cartera',
        'cosecha' => 'cosecha',
        'sub cosecha' => 'sub_cosecha',
        'producto' => 'producto',
        'sub producto' => 'sub_producto',
        'historico' => 'historico',
        'departamento' => 'departamento',
        'deuda total' => 'deuda_total',
        'deuda capital' => 'deuda_capital',
        'campana' => 'campania',
        '%' => 'porcentaje',
        'ano nacimiento' => 'fecha_nacimiento',
        'edad' => 'edad',
        'entidades' => 'entidades',
        'negocio' => 'negocio',
        'sueldo' => 'sueldo',
        'situacion laboral' => 'situacion_laboral',
        'ano laboral' => 'anio_laboral',
        'sexo' => 'sexo',
        'rango sueldo' => 'rango_sueldo',
        'ano castigo' => 'anio_castigo',
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
        self::ASIGNACIONES => [
            'periodo' => 'PERIODO',
            'empresa' => 'EMPRESA',
            'documento' => 'DOCUMENTO',
            'titular' => 'TITULAR',
            'codigo' => 'CODIGO',
            'tipo_cartera' => 'TIPO DE CARTERA',
            'cosecha' => 'COSECHA',
            'sub_cosecha' => 'SUB COSECHA',
            'producto' => 'PRODUCTO',
            'sub_producto' => 'SUB_PRODUCTO',
            'historico' => 'HISTORICO',
            'departamento' => 'DEPARTAMENTO',
            'deuda_total' => 'DEUDA TOTAL',
            'deuda_capital' => 'DEUDA CAPITAL',
            'campania' => 'CAMPAÑA',
            'porcentaje' => '%',
            'fecha_nacimiento' => 'AÑO_NACIMIENTO',
            'edad' => 'EDAD',
            'entidades' => 'ENTIDADES',
            'negocio' => 'NEGOCIO',
            'sueldo' => 'SUELDO',
            'situacion_laboral' => 'SITUACION_LABORAL',
            'anio_laboral' => 'AÑO_LABORAL',
            'sexo' => 'SEXO',
            'rango_sueldo' => 'RANGO_SUELDO',
            'anio_castigo' => 'AÑO_CASTIGO',
        ],
    ];

    public function __construct(
        private readonly NormalizadorExpertisService $normalizador,
        private readonly AsignacionExpertisDataMapper $asignaciones,
        private readonly ExpertisSharedStringsCachingStrategyFactory $sharedStrings,
    ) {}

    public function inspeccionar(string $ruta, string $tipo, int $limite = 20): array
    {
        $reader = new Reader(cachingStrategyFactory: $this->sharedStrings);
        $reader->open($ruta);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $encabezados = null;
                $mapa = [];
                $vistaPrevia = [];
                $numeroFila = 0;
                $periodos = [];
                $empresas = [];
                $totalFilas = 0;
                $faltantes = null;

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
                        $faltantes = $this->columnasFaltantes($mapa, $tipo);

                        continue;
                    }

                    $totalFilas++;
                    $asociada = $this->asociar($valores, $mapa, true);

                    if (count($vistaPrevia) < $limite) {
                        if ($tipo === self::ASIGNACIONES) {
                            $asociada = $this->prepararAsignacionParaVistaPrevia($asociada);
                        }

                        $vistaPrevia[] = $asociada;
                    }

                    if ($tipo === self::ASIGNACIONES && $faltantes === []) {
                        try {
                            $periodo = $this->asignaciones->periodo(
                                $asociada['periodo'] ?? null,
                            );
                        } catch (\InvalidArgumentException $exception) {
                            throw new RuntimeException(
                                $exception->getMessage(),
                                0,
                                $exception,
                            );
                        }
                        $periodos[$periodo] = true;

                        $empresa = $this->normalizador->texto(
                            $asociada['empresa'] ?? null,
                            50,
                        );
                        if ($empresa === null) {
                            throw new RuntimeException('La empresa es obligatoria.');
                        }
                        if ($empresa !== 'EXPERTIS') {
                            throw new RuntimeException(
                                'El archivo de asignación contiene una empresa distinta de EXPERTIS.',
                            );
                        }
                        $empresas[$empresa] = true;

                        continue;
                    }

                    if (count($vistaPrevia) >= $limite) {
                        break;
                    }
                }

                if ($encabezados === null) {
                    throw new RuntimeException('El archivo no contiene encabezados ni filas legibles.');
                }

                $presentes = array_values(array_filter($mapa));
                $faltantes ??= $this->columnasFaltantes($mapa, $tipo);

                if (
                    $tipo === self::ASIGNACIONES
                    && $faltantes === []
                    && count($periodos) > 1
                ) {
                    throw new RuntimeException(
                        'El archivo debe contener un único periodo de asignación.',
                    );
                }

                $resultado = [
                    'encabezados' => $encabezados,
                    'columnas_detectadas' => array_values(array_unique($presentes)),
                    'columnas_faltantes' => $faltantes,
                    'preview' => $vistaPrevia,
                ];

                if ($tipo === self::ASIGNACIONES) {
                    $periodoDetectado = array_key_first($periodos);
                    $resultado['periodo_detectado'] = $periodoDetectado === null
                        ? null
                        : (string) $periodoDetectado;
                    $resultado['empresa_detectada'] = array_key_first($empresas);
                    $resultado['total_filas_detectadas'] = $totalFilas;
                }

                return $resultado;
            }
        } finally {
            $reader->close();
        }

        throw new RuntimeException('El archivo XLSX no contiene hojas.');
    }

    public function filas(string $ruta, string $tipo): Generator
    {
        $reader = new Reader(cachingStrategyFactory: $this->sharedStrings);
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

    /**
     * Recorre la primera hoja de Asignaciones una sola vez.
     *
     * @return array{
     *     encabezados: array,
     *     columnas_detectadas: array,
     *     columnas_faltantes: array,
     *     total_filas_detectadas: int
     * }
     */
    public function recorrerAsignaciones(string $ruta, callable $procesarFila): array
    {
        $reader = new Reader(cachingStrategyFactory: $this->sharedStrings);
        $reader->open($ruta);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $encabezados = null;
                $mapa = [];
                $numeroFila = 0;
                $totalFilas = 0;

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
                        $mapa = $this->mapearEncabezados(
                            $encabezados,
                            self::ASIGNACIONES,
                        );
                        $faltantes = $this->columnasFaltantes(
                            $mapa,
                            self::ASIGNACIONES,
                        );

                        if ($faltantes !== []) {
                            throw new RuntimeException(
                                'Faltan columnas obligatorias: '.implode(', ', $faltantes),
                            );
                        }

                        continue;
                    }

                    $totalFilas++;
                    $procesarFila([
                        'numero_fila' => $numeroFila,
                        'datos' => $this->asociar($valores, $mapa),
                        'originales' => array_map(
                            fn ($valor) => $valor instanceof \DateTimeInterface
                                ? $valor->format('Y-m-d H:i:s')
                                : $valor,
                            $valores,
                        ),
                    ]);
                }

                if ($encabezados === null) {
                    throw new RuntimeException(
                        'El archivo no contiene encabezados ni filas legibles.',
                    );
                }

                return [
                    'encabezados' => $encabezados,
                    'columnas_detectadas' => array_values(array_unique(
                        array_values(array_filter($mapa)),
                    )),
                    'columnas_faltantes' => [],
                    'total_filas_detectadas' => $totalFilas,
                ];
            }
        } finally {
            $reader->close();
        }

        throw new RuntimeException('El archivo XLSX no contiene hojas.');
    }

    private function mapearEncabezados(array $encabezados, string $tipo): array
    {
        $definiciones = match ($tipo) {
            self::GESTIONES => self::COLUMNAS_GESTIONES,
            self::PAGOS => self::COLUMNAS_PAGOS,
            self::ASIGNACIONES => self::COLUMNAS_ASIGNACIONES,
            default => throw new RuntimeException('Tipo de archivo Expertis no válido.'),
        };

        return array_map(function ($encabezado) use ($definiciones) {
            $normalizado = $this->normalizador->encabezado((string) ($encabezado ?? ''));

            return $definiciones[$normalizado] ?? null;
        }, $encabezados);
    }

    private function asociar(
        array $valores,
        array $mapa,
        bool $paraVistaPrevia = false,
    ): array {
        $fila = [];
        foreach ($mapa as $indice => $clave) {
            if ($clave !== null) {
                $valor = $valores[$indice] ?? null;
                $fila[$clave] = $paraVistaPrevia
                    ? $this->valorVistaPrevia($clave, $valor)
                    : $valor;
            }
        }

        return $fila;
    }

    private function valorVistaPrevia(string $clave, mixed $valor): mixed
    {
        if (in_array(
            $clave,
            ['fecha', 'fecha_llamada', 'fecha_compromiso', 'fecha_nacimiento'],
            true,
        )) {
            return $this->normalizador->fecha($valor) ?? $valor;
        }

        if ($clave === 'hora') {
            return $this->normalizador->hora($valor) ?? $valor;
        }

        if (! $valor instanceof \DateTimeInterface) {
            return $valor;
        }

        return $valor->format('Y-m-d H:i:s');
    }

    private function prepararAsignacionParaVistaPrevia(array $fila): array
    {
        $documento = $this->normalizador->documento(
            $fila['documento'] ?? $fila['dni'] ?? null,
        );
        $tipoCartera = $this->normalizador->cartera($fila['tipo_cartera'] ?? null);

        if ($documento !== '' && $tipoCartera !== '') {
            $fila['codigo'] = $this->normalizador->codigoVisible(
                $documento,
                $tipoCartera,
            );
        }

        return $fila;
    }

    private function columnasFaltantes(array $mapa, string $tipo): array
    {
        $presentes = array_values(array_filter($mapa));
        $faltantes = [];

        foreach (self::OBLIGATORIAS[$tipo] as $clave => $etiqueta) {
            if (! in_array($clave, $presentes, true)) {
                $faltantes[] = $etiqueta;
            }
        }

        return $faltantes;
    }

    private function filaVacia(array $valores): bool
    {
        foreach ($valores as $valor) {
            if ($valor instanceof \DateTimeInterface) {
                return false;
            }

            if ($valor !== null && trim((string) $valor) !== '') {
                return false;
            }
        }

        return true;
    }
}
