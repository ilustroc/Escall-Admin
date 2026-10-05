<?php

namespace App\Services\Cargas;

use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOStatement;

class LlamadasSpService
{
    public const BATCH_SIZE = 2000;

    /** @var (callable(string, string): iterable<array<string, mixed>)|null */
    private $rowSource;

    /**
     * The optional source is deliberately limited to tests. Production always
     * streams directly from the remote stored procedure.
     *
     * @param (callable(string, string): iterable<array<string, mixed>)|null $rowSource
     */
    public function __construct(?callable $rowSource = null)
    {
        $this->rowSource = $rowSource;
    }

    /**
     * Synchronizes one semi-open interval [from, to). A transaction protects
     * the local replacement, so a failed remote read never leaves a partial day.
     *
     * @return array{obtenidos:int,insertados:int,errores:int,rango:array{desde:string,hasta:string},tiempo:float,muestra:array<int,array<string,mixed>>}
     */
    public function sync(string $from, string $to, bool $dryRun = false): array
    {
        [$fromDate, $toDate] = $this->validatedRange($from, $to);
        $startedAt = microtime(true);
        $stats = [
            'obtenidos' => 0,
            'insertados' => 0,
            'errores' => 0,
            'rango' => [
                'desde' => $fromDate->format('Y-m-d H:i:s'),
                'hasta' => $toDate->format('Y-m-d H:i:s'),
            ],
            'tiempo' => 0.0,
            'muestra' => [],
        ];

        set_time_limit(0);
        DB::connection()->disableQueryLog();

        try {
            if ($dryRun) {
                foreach ($this->streamRows($stats['rango']['desde'], $stats['rango']['hasta']) as $row) {
                    $stats['obtenidos']++;
                    $mapped = $this->mapRow($row);

                    if ($mapped === null || ! $this->isWithinRange($mapped['fecha_gestion'], $fromDate, $toDate)) {
                        $stats['errores']++;

                        continue;
                    }

                    if (count($stats['muestra']) < 5) {
                        $stats['muestra'][] = $mapped;
                    }
                }
            } else {
                DB::transaction(function () use (&$stats, $fromDate, $toDate): void {
                    DB::table('llamadas')
                        ->where('fecha_gestion', '>=', $fromDate->format('Y-m-d H:i:s'))
                        ->where('fecha_gestion', '<', $toDate->format('Y-m-d H:i:s'))
                        ->delete();

                    $batch = [];

                    foreach ($this->streamRows($fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s')) as $row) {
                        $stats['obtenidos']++;
                        $mapped = $this->mapRow($row);

                        if ($mapped === null || ! $this->isWithinRange($mapped['fecha_gestion'], $fromDate, $toDate)) {
                            $stats['errores']++;

                            continue;
                        }

                        if (count($stats['muestra']) < 5) {
                            $stats['muestra'][] = $mapped;
                        }

                        $batch[] = $mapped;

                        if (count($batch) >= self::BATCH_SIZE) {
                            DB::table('llamadas')->insert($batch);
                            $stats['insertados'] += count($batch);
                            $batch = [];
                        }
                    }

                    if ($batch !== []) {
                        DB::table('llamadas')->insert($batch);
                        $stats['insertados'] += count($batch);
                    }
                }, 1);
            }
        } finally {
            $stats['tiempo'] = round(microtime(true) - $startedAt, 3);
        }

        return $stats;
    }

    /** @return Generator<int, array<string, mixed>> */
    protected function streamRows(string $from, string $to): Generator
    {
        if ($this->rowSource !== null) {
            foreach (($this->rowSource)($from, $to) as $row) {
                yield $row;
            }

            return;
        }

        $statement = $this->openStatement($from, $to);

        try {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                yield $row;
            }
        } finally {
            $this->closeStatement($statement);
        }
    }

    protected function openStatement(string $from, string $to): PDOStatement
    {
        $pdo = DB::connection('sp')->getPdo();

        // MySQL/MariaDB streams the result instead of buffering it in PHP.
        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        $statement = $pdo->prepare('CALL ESCALL.sp_gestiones_nc(?, ?)');
        $statement->execute([$from, $to]);

        return $statement;
    }

    protected function closeStatement(PDOStatement $statement): void
    {
        while ($statement->nextRowset()) {
            // MariaDB can return additional empty result sets after CALL.
        }

        $statement->closeCursor();
    }

    /** @return array<string, mixed>|null */
    public function mapRow(array $row): ?array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = $value;
        }

        $fechaGestion = $this->dateTime($normalized['fecha_gestion'] ?? null);
        if ($fechaGestion === null) {
            return null;
        }

        return [
            'fecha_gestion' => $fechaGestion,
            'fecha_inicio' => $this->dateTime($normalized['fecha_inicio'] ?? null),
            'fecha_final' => $this->dateTime($normalized['fecha_final'] ?? null),
            'documento' => $this->text($normalized['documento'] ?? null, 30),
            // Never trim or truncate cuenta: it can contain several accounts and line breaks.
            'cuenta' => $this->preservedText($normalized['cuenta'] ?? null),
            'telefono' => $this->text($normalized['telefono'] ?? null, 30),
            'variable' => $this->text($normalized['variable'] ?? null, 255),
            'cliente' => $this->text($normalized['cliente'] ?? null, 255),
            'cartera' => $this->text($normalized['cartera'] ?? null, 150),
            'campana' => $this->text($normalized['campana'] ?? null, 255),
            'codigo_estado' => $this->text($normalized['codigo_estado'] ?? null, 30),
            'resultado' => $this->text($normalized['resultado'] ?? null, 150),
            'tipo_gestion' => $this->text($normalized['tipo_gestion'] ?? null, 150),
            'observacion' => $this->preservedText($normalized['observacion'] ?? null),
            'usuario' => $this->text($normalized['usuario'] ?? null, 100),
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function validatedRange(string $from, string $to): array
    {
        $fromDate = $this->strictDateTime($from, 'from');
        $toDate = $this->strictDateTime($to, 'to');

        if ($fromDate->greaterThanOrEqualTo($toDate)) {
            throw new \InvalidArgumentException('La fecha --from debe ser anterior a --to.');
        }

        return [$fromDate, $toDate];
    }

    private function strictDateTime(string $value, string $name): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $value, 'America/Lima');
        $errors = CarbonImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d H:i:s') !== $value
        ) {
            throw new \InvalidArgumentException("La fecha --{$name} debe tener formato YYYY-MM-DD HH:MM:SS.");
        }

        return $date;
    }

    private function isWithinRange(string $date, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        $value = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $date, 'America/Lima');

        return $value !== false && $value->greaterThanOrEqualTo($from) && $value->lessThan($to);
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $value = trim((string) ($value ?? ''));
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        foreach (['!Y-m-d H:i:s', '!Y-m-d H:i', '!Y-m-d\\TH:i:s'] as $format) {
            $date = CarbonImmutable::createFromFormat($format, $value, 'America/Lima');
            $errors = CarbonImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        $key = mb_strtolower($key, 'UTF-8');
        $key = strtr($key, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';

        return trim($key, '_');
    }

    private function text(mixed $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    private function preservedText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }
}
