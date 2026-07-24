<?php

namespace App\Services\Expertis;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class NormalizadorExpertisService
{
    public function encabezado(string $valor): string
    {
        $valor = Str::ascii(mb_strtolower(trim($valor), 'UTF-8'));
        $valor = str_replace('_', ' ', $valor);

        return preg_replace('/\s+/', ' ', $valor) ?? $valor;
    }

    public function dni(mixed $valor): string
    {
        $digitos = preg_replace('/\D+/', '', $this->textoCrudo($valor)) ?? '';

        if ($digitos !== '' && strlen($digitos) <= 8) {
            return str_pad($digitos, 8, '0', STR_PAD_LEFT);
        }

        return $digitos;
    }

    public function cartera(mixed $valor): string
    {
        return $this->texto($valor) ?? '';
    }

    public function codigoVisible(mixed $dni, mixed $cartera): string
    {
        $documento = $this->dni($dni);
        $carteraNormalizada = $this->cartera($cartera);

        return trim($documento.'-'.$carteraNormalizada, '-');
    }

    public function codigoNormalizado(mixed $codigo): string
    {
        $codigo = $this->texto($codigo) ?? '';
        [$documento, $cartera] = array_pad(explode('-', $codigo, 2), 2, '');
        $documento = preg_replace('/\D+/', '', $documento) ?? '';
        $documento = ltrim($documento, '0');
        $documento = $documento === '' ? '0' : $documento;

        return trim($documento.'-'.$this->cartera($cartera), '-');
    }

    public function cuentaVisible(mixed $valor): string
    {
        return $this->texto($valor) ?? '';
    }

    public function telefono(mixed $valor): ?string
    {
        $telefono = preg_replace('/\D+/', '', $this->textoCrudo($valor)) ?? '';

        return $telefono === '' ? null : mb_substr($telefono, 0, 30);
    }

    public function texto(mixed $valor, ?int $maximo = null): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($this->textoCrudo($valor));
        if ($this->esNuloLiteral($texto)) {
            return null;
        }

        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;
        $texto = mb_strtoupper($texto, 'UTF-8');

        if ($texto === '') {
            return null;
        }

        return $maximo ? mb_substr($texto, 0, $maximo) : $texto;
    }

    public function textoLibre(mixed $valor, ?int $maximo = null): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($this->textoCrudo($valor));
        if ($this->esNuloLiteral($texto)) {
            return null;
        }

        $texto = preg_replace('/[ \t]+/', ' ', $texto) ?? $texto;

        if ($texto === '') {
            return null;
        }

        return $maximo ? mb_substr($texto, 0, $maximo) : $texto;
    }

    public function fecha(mixed $valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return Carbon::instance($valor)->format('Y-m-d');
        }

        if (is_numeric($valor) && (float) $valor > 0) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $valor))
                    ->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $texto = trim($this->textoCrudo($valor));
        if ($texto === '' || $this->esNuloLiteral($texto)) {
            return null;
        }

        $fecha = $this->fechaDmy($texto) ?? $this->fechaYmd($texto);
        if ($fecha !== null) {
            return $fecha;
        }

        return null;
    }

    public function hora(mixed $valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return Carbon::instance($valor)->format('H:i:s');
        }

        if (is_numeric($valor)) {
            $fraccion = (float) $valor - floor((float) $valor);
            $segundos = (int) round($fraccion * 86400) % 86400;

            return gmdate('H:i:s', $segundos);
        }

        $texto = trim($this->textoCrudo($valor));
        if ($texto === '') {
            return null;
        }

        foreach (['!H:i:s', '!H:i'] as $formato) {
            $hora = Carbon::createFromFormat($formato, $texto);
            if ($hora !== false && $hora->format(ltrim($formato, '!')) === $texto) {
                return $hora->format('H:i:s');
            }
        }

        return null;
    }

    public function monto(mixed $valor): ?string
    {
        if (is_int($valor) || is_float($valor)) {
            return number_format((float) $valor, 2, '.', '');
        }

        $texto = trim($this->textoCrudo($valor));
        if ($texto === '') {
            return null;
        }

        $negativo = str_contains($texto, '(') && str_contains($texto, ')');
        $texto = preg_replace('/[^0-9,.\-]/', '', $texto) ?? '';
        if ($texto === '' || $texto === '-') {
            return null;
        }

        $ultimaComa = strrpos($texto, ',');
        $ultimoPunto = strrpos($texto, '.');

        if ($ultimaComa !== false && $ultimoPunto !== false) {
            $decimal = $ultimaComa > $ultimoPunto ? ',' : '.';
            $miles = $decimal === ',' ? '.' : ',';
            $texto = str_replace($miles, '', $texto);
            $texto = str_replace($decimal, '.', $texto);
        } elseif ($ultimaComa !== false) {
            $decimales = strlen($texto) - $ultimaComa - 1;
            $texto = $decimales >= 1 && $decimales <= 2
                ? str_replace(',', '.', $texto)
                : str_replace(',', '', $texto);
        } elseif ($ultimoPunto !== false) {
            $decimales = strlen($texto) - $ultimoPunto - 1;
            if ($decimales > 2) {
                $texto = str_replace('.', '', $texto);
            }
        }

        if (! is_numeric($texto)) {
            return null;
        }

        $monto = (float) $texto;
        if ($negativo) {
            $monto *= -1;
        }

        return number_format($monto, 2, '.', '');
    }

    public function hashGestion(array $gestion): string
    {
        $valores = [
            $gestion['codigo_normalizado'] ?? '',
            $gestion['telefono'] ?? '',
            $gestion['fecha_llamada'] ?? '',
            $gestion['hora'] ?? '00:00:00',
            $this->texto($gestion['asesor'] ?? null) ?? '',
            $this->texto($gestion['campania'] ?? null) ?? '',
            $this->texto($gestion['nivel_1'] ?? null) ?? '',
            $this->texto($gestion['nivel_2'] ?? null) ?? '',
            $this->texto($gestion['medio_gestion'] ?? null) ?? '',
        ];

        $camposCriticosIncompletos = empty($gestion['hora'])
            || empty($gestion['telefono'])
            || empty($gestion['asesor'])
            || empty($gestion['nivel_2']);

        if ($camposCriticosIncompletos) {
            $valores[] = $this->texto($gestion['observacion'] ?? null) ?? '';
        }

        return hash('sha256', json_encode($valores, JSON_UNESCAPED_UNICODE));
    }

    public function hashPago(array $pago): string
    {
        return hash('sha256', json_encode([
            $pago['cuenta_normalizada'] ?? '',
            $pago['fecha'] ?? '',
            number_format((float) ($pago['monto'] ?? 0), 2, '.', ''),
            $this->texto($pago['ejecutivo'] ?? null) ?? '',
            $this->texto($pago['tipo_acuerdo'] ?? null) ?? '',
            $this->texto($pago['recaudo'] ?? null) ?? '',
        ], JSON_UNESCAPED_UNICODE));
    }

    public function montoProyectado(mixed $monto, mixed $nivel2, mixed $observacion): string
    {
        $montoNormalizado = $this->monto($monto) ?? '0.00';
        if ((float) $montoNormalizado > 0) {
            return $montoNormalizado;
        }

        if (($this->texto($nivel2) ?? '') !== 'VLL') {
            return '0.00';
        }

        $texto = $this->textoLibre($observacion) ?? '';
        if (! preg_match(
            '/MONTO\s+TOTAL\s+NEGOCIADO\s*:?\s*(?:S\/\.?\s*)?([0-9][0-9.,\s]*?)(?=\s*\/|$|[A-ZÁÉÍÓÚÑ])/iu',
            $texto,
            $coincidencia,
        )) {
            return '0.00';
        }

        return $this->monto($coincidencia[1]) ?? '0.00';
    }

    private function textoCrudo(mixed $valor): string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) ($valor ?? '');
    }

    private function fechaDmy(string $valor): ?string
    {
        if (! preg_match(
            '/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?$/',
            $valor,
            $partes,
        )) {
            return null;
        }

        return $this->construirFecha(
            (int) $partes[3],
            (int) $partes[2],
            (int) $partes[1],
            $partes,
        );
    }

    private function fechaYmd(string $valor): ?string
    {
        if (! preg_match(
            '/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?$/',
            $valor,
            $partes,
        )) {
            return null;
        }

        return $this->construirFecha(
            (int) $partes[1],
            (int) $partes[2],
            (int) $partes[3],
            $partes,
        );
    }

    private function construirFecha(int $anio, int $mes, int $dia, array $partes): ?string
    {
        $hora = isset($partes[4]) && $partes[4] !== '' ? (int) $partes[4] : 0;
        $minuto = isset($partes[5]) && $partes[5] !== '' ? (int) $partes[5] : 0;
        $segundo = isset($partes[6]) && $partes[6] !== '' ? (int) $partes[6] : 0;

        if (
            ! checkdate($mes, $dia, $anio)
            || $hora > 23
            || $minuto > 59
            || $segundo > 59
        ) {
            return null;
        }

        return Carbon::create($anio, $mes, $dia)->format('Y-m-d');
    }

    private function esNuloLiteral(string $valor): bool
    {
        return mb_strtoupper(trim($valor), 'UTF-8') === 'NULL';
    }
}
