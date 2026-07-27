<?php

namespace App\Services\Expertis;

use DateTimeInterface;
use InvalidArgumentException;

class AsignacionExpertisDataMapper
{
    public function __construct(
        private readonly NormalizadorExpertisService $normalizador,
    ) {}

    public function map(
        array $fila,
        ?int $importacionId = null,
        ?int $numeroFila = null,
    ): array {
        $periodo = $this->periodo($fila['periodo'] ?? null);
        $empresa = $this->normalizador->texto($fila['empresa'] ?? null, 50);
        if ($empresa === null) {
            throw new InvalidArgumentException('La empresa es obligatoria.');
        }
        if ($empresa !== 'EXPERTIS') {
            throw new InvalidArgumentException(
                'El archivo de asignación contiene una empresa distinta de EXPERTIS.',
            );
        }

        $dni = $this->normalizador->dni($fila['dni'] ?? null);
        if (! preg_match('/^\d{8}$/', $dni)) {
            throw new InvalidArgumentException('El DNI debe contener exactamente ocho dígitos.');
        }

        $titular = $this->normalizador->textoLibre($fila['titular'] ?? null, 255);
        if ($titular === null) {
            throw new InvalidArgumentException('El titular es obligatorio.');
        }

        $tipoCartera = $this->normalizador->cartera($fila['tipo_cartera'] ?? null);
        if ($tipoCartera === '') {
            throw new InvalidArgumentException('El tipo de cartera es obligatorio.');
        }
        $tipoCartera = mb_substr($tipoCartera, 0, 100);

        $codigo = $this->normalizador->codigoVisible($dni, $tipoCartera);
        $codigoNormalizado = $this->normalizador->codigoNormalizado($codigo);
        $codigoEnviado = $this->textoOpcional($fila['codigo'] ?? null, 150);
        if (
            $codigoEnviado !== null
            && $this->normalizador->codigoNormalizado($codigoEnviado) !== $codigoNormalizado
        ) {
            throw new InvalidArgumentException(
                'El código no coincide con el DNI y el tipo de cartera para Expertis.',
            );
        }

        $deudaTotal = $this->montoObligatorio(
            $fila['deuda_total'] ?? null,
            'La deuda total es obligatoria y debe tener un formato válido.',
        );
        $deudaCapital = $this->montoObligatorio(
            $fila['deuda_capital'] ?? null,
            'La deuda capital es obligatoria y debe tener un formato válido.',
        );

        $fechaNacimiento = $this->fechaOpcional(
            $fila['fecha_nacimiento'] ?? null,
            'El año de nacimiento no tiene un formato de fecha válido.',
        );
        $sexo = $this->textoOpcional($fila['sexo'] ?? null, 10);
        if ($sexo !== null && ! in_array($sexo, ['M', 'F'], true)) {
            throw new InvalidArgumentException('El sexo debe ser M o F.');
        }

        $asignacion = [
            'importacion_expertis_id' => $importacionId,
            'periodo' => $periodo,
            'empresa' => $empresa,
            'dni' => $dni,
            'titular' => $titular,
            'codigo' => $codigo,
            'codigo_normalizado' => $codigoNormalizado,
            'tipo_cartera' => $tipoCartera,
            'cosecha' => $this->textoOpcional($fila['cosecha'] ?? null, 150),
            'sub_cosecha' => $this->textoOpcional($fila['sub_cosecha'] ?? null, 150),
            'producto' => $this->textoOpcional($fila['producto'] ?? null, 100),
            'sub_producto' => $this->textoOpcional($fila['sub_producto'] ?? null, 100),
            'historico' => $this->textoOpcional($fila['historico'] ?? null, 100),
            'departamento' => $this->textoOpcional($fila['departamento'] ?? null, 100),
            'deuda_total' => $deudaTotal,
            'deuda_capital' => $deudaCapital,
            'campania' => $this->montoOpcional(
                $fila['campania'] ?? null,
                'La campaña no tiene un formato válido.',
            ),
            'porcentaje' => $this->porcentaje($fila['porcentaje'] ?? null),
            'fecha_nacimiento' => $fechaNacimiento,
            'edad' => $this->enteroOpcional(
                $fila['edad'] ?? null,
                0,
                120,
                'La edad debe ser un entero entre 0 y 120.',
            ),
            'entidades' => $this->enteroOpcional(
                $fila['entidades'] ?? null,
                0,
                65535,
                'Entidades debe ser un entero no negativo.',
            ),
            'negocio' => $this->textoOpcional($fila['negocio'] ?? null, 100),
            'sueldo' => $this->montoOpcional(
                $fila['sueldo'] ?? null,
                'El sueldo no tiene un formato válido.',
            ),
            'situacion_laboral' => $this->textoOpcional(
                $fila['situacion_laboral'] ?? null,
                100,
            ),
            'anio_laboral' => $this->enteroOpcional(
                $fila['anio_laboral'] ?? null,
                1900,
                2100,
                'El año laboral debe ser un entero entre 1900 y 2100.',
            ),
            'sexo' => $sexo,
            'rango_sueldo' => $this->textoOpcional($fila['rango_sueldo'] ?? null, 100),
            'anio_castigo' => $this->enteroOpcional(
                $fila['anio_castigo'] ?? null,
                1900,
                2100,
                'El año de castigo debe ser un entero entre 1900 y 2100.',
            ),
            'numero_fila_origen' => $numeroFila,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $asignacion['hash_fila'] = $this->normalizador->hashAsignacion($asignacion);

        return $asignacion;
    }

    public function periodo(mixed $valor): string
    {
        if (is_float($valor) && floor($valor) !== $valor) {
            throw new InvalidArgumentException(
                'El periodo debe tener el formato YYYYMM con seis dígitos.',
            );
        }

        $periodo = trim((string) ($valor ?? ''));
        if (! preg_match('/^\d{6}$/', $periodo)) {
            throw new InvalidArgumentException(
                'El periodo debe tener el formato YYYYMM con seis dígitos.',
            );
        }

        $anio = (int) substr($periodo, 0, 4);
        $mes = (int) substr($periodo, 4, 2);
        if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
            throw new InvalidArgumentException(
                'El periodo debe contener un año entre 2000 y 2100 y un mes válido.',
            );
        }

        return $periodo;
    }

    private function textoOpcional(mixed $valor, int $maximo): ?string
    {
        if ($this->esVacioOpcional($valor)) {
            return null;
        }

        return $this->normalizador->texto($valor, $maximo);
    }

    private function montoObligatorio(mixed $valor, string $mensaje): string
    {
        if ($this->esVacioOpcional($valor)) {
            throw new InvalidArgumentException($mensaje);
        }

        $monto = $this->normalizador->monto($valor);
        if ($monto === null) {
            throw new InvalidArgumentException($mensaje);
        }

        return $monto;
    }

    private function montoOpcional(mixed $valor, string $mensaje): ?string
    {
        if ($this->esVacioOpcional($valor)) {
            return null;
        }

        $monto = $this->normalizador->monto($valor);
        if ($monto === null) {
            throw new InvalidArgumentException($mensaje);
        }

        return $monto;
    }

    private function porcentaje(mixed $valor): ?string
    {
        if ($this->esVacioOpcional($valor)) {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return number_format((float) $valor, 4, '.', '');
        }

        $texto = str_replace('%', '', trim((string) $valor));
        $texto = preg_replace('/\s+/', '', $texto) ?? '';
        $ultimaComa = strrpos($texto, ',');
        $ultimoPunto = strrpos($texto, '.');

        if ($ultimaComa !== false && $ultimoPunto !== false) {
            $decimal = $ultimaComa > $ultimoPunto ? ',' : '.';
            $miles = $decimal === ',' ? '.' : ',';
            $texto = str_replace($miles, '', $texto);
            $texto = str_replace($decimal, '.', $texto);
        } elseif ($ultimaComa !== false) {
            $texto = str_replace(',', '.', $texto);
        }

        if (! is_numeric($texto)) {
            throw new InvalidArgumentException('El porcentaje no tiene un formato válido.');
        }

        return number_format((float) $texto, 4, '.', '');
    }

    private function fechaOpcional(mixed $valor, string $mensaje): ?string
    {
        if ($this->esVacioOpcional($valor)) {
            return null;
        }

        $fecha = $this->normalizador->fecha($valor);
        if ($fecha === null) {
            throw new InvalidArgumentException($mensaje);
        }

        return $fecha;
    }

    private function enteroOpcional(
        mixed $valor,
        int $minimo,
        int $maximo,
        string $mensaje,
    ): ?int {
        if ($this->esVacioOpcional($valor)) {
            return null;
        }

        if (
            ! is_numeric($valor)
            || (float) $valor !== floor((float) $valor)
            || (int) $valor < $minimo
            || (int) $valor > $maximo
        ) {
            throw new InvalidArgumentException($mensaje);
        }

        return (int) $valor;
    }

    private function esVacioOpcional(mixed $valor): bool
    {
        if ($valor === null) {
            return true;
        }

        if ($valor instanceof DateTimeInterface) {
            return false;
        }

        return in_array(
            mb_strtoupper(trim((string) $valor), 'UTF-8'),
            ['', '-', 'NULL'],
            true,
        );
    }
}
