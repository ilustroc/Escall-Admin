<?php

namespace App\Services\Cargas;

use DateTime;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOStatement;

class GestionesSpService
{
    private const BATCH_SIZE = 2000;

    public function preview(string $from, string $to, int $limit = 100): array
    {
        $statement = $this->openStatement($from, $to);
        $rows = [];
        $total = 0;

        try {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $total++;

                if (count($rows) >= $limit) {
                    continue;
                }

                $mapped = $this->mapRow($row);
                if ($mapped !== null) {
                    $rows[] = $mapped;
                }
            }
        } finally {
            $this->closeStatement($statement);
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
        ];
    }

    public function import(string $from, string $to): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        DB::connection()->disableQueryLog();

        $statement = $this->openStatement($from, $to);

        try {
            return DB::transaction(function () use ($from, $to, $statement): int {
                DB::table('gestiones')
                    ->whereBetween('fecha_gestion', [$from, $to])
                    ->delete();

                return $this->streamIntoLocal($statement);
            }, 1);
        } finally {
            $this->closeStatement($statement);
        }
    }

    private function openStatement(string $from, string $to): PDOStatement
    {
        $pdo = DB::connection('sp')->getPdo();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $statement = $pdo->prepare('CALL sp_gestiones(?, ?)');
        $statement->execute([$from, $to]);

        return $statement;
    }

    private function closeStatement(PDOStatement $statement): void
    {
        while ($statement->nextRowset()) {
            // Consume todos los rowsets adicionales devueltos por MariaDB.
        }

        $statement->closeCursor();
    }

    private function streamIntoLocal(PDOStatement $statement): int
    {
        $batch = [];
        $inserted = 0;
        $now = now()->toDateTimeString();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $mapped = $this->mapRow($row);
            if ($mapped === null) {
                continue;
            }

            $batch[] = array_merge($mapped, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (count($batch) >= self::BATCH_SIZE) {
                DB::table('gestiones')->insert($batch);
                $inserted += count($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('gestiones')->insert($batch);
            $inserted += count($batch);
        }

        return $inserted;
    }

    private function mapRow(array $row): ?array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = $value;
        }

        $date = $this->date(
            $normalized['fecha_gestion']
                ?? $normalized['fecha_de_gestion']
                ?? $normalized['fecha_gestion_']
                ?? null,
        );
        $dni = $normalized['dni'] ?? $normalized['documento'] ?? null;

        if ($date === null || trim((string) $dni) === '') {
            return null;
        }

        return [
            'fecha_gestion' => $date,
            'dni' => trim((string) $dni),
            'telefono' => $this->cut($normalized['telefono'] ?? $normalized['celular'] ?? null, 25),
            'status' => $this->defaultValue(
                $normalized['status']
                    ?? $normalized['tipo_de_contacto']
                    ?? $normalized['contacto']
                    ?? null,
                'NO CONTACTO',
            ),
            'tipificacion' => $this->defaultValue(
                $normalized['tipificacion']
                    ?? $normalized['tipo_de_resultado']
                    ?? $normalized['resultado']
                    ?? null,
                'NO CONTESTA',
            ),
            'observacion' => $this->cut($normalized['observacion'] ?? $normalized['obs'] ?? null, 500),
            'fecha_pago' => $this->date($normalized['fecha_pago'] ?? null),
            'monto_pago' => $this->integer($normalized['monto_pago'] ?? $normalized['montopago'] ?? null),
            'nombre' => $this->cut(
                $normalized['nombre'] ?? $normalized['agente'] ?? $normalized['usuario'] ?? null,
                150,
            ),
        ];
    }

    private function normalizeKey(string $key): string
    {
        $key = mb_strtolower($key, 'UTF-8');
        $key = strtr($key, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';

        return trim($key, '_');
    }

    private function defaultValue(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return $value === '' ? $default : $value;
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || in_array($value, ['0000-00-00', '00/00/0000', '00000000'], true)) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $value)) {
            $date = substr($value, 0, 10);

            try {
                $parsed = new DateTime($date);
                $year = (int) $parsed->format('Y');

                return $year >= 1900 && $year <= 2100 ? $parsed->format('Y-m-d') : null;
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches)) {
            $year = (int) $matches[3];
            $month = (int) $matches[2];
            $day = (int) $matches[1];

            if ($year >= 1900 && $year <= 2100 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    private function integer(mixed $value): ?int
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || $value === '?' || $value === '-') {
            return null;
        }

        $value = preg_replace('/[^\d\-]/', '', $value) ?? '';

        return $value !== '' && is_numeric($value) ? (int) $value : null;
    }

    private function cut(mixed $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }
}
