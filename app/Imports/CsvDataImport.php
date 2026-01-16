<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;

class CsvDataImport
{
    /** @var int Tamaño de lote para upsert */
    private int $batchSize;

    /** Columnas que se actualizan en conflicto (para upsert) */
    private array $updateCols = [
        'dni','titular','cartera','entidad','cosecha','sub_cartera','producto',
        'sub_producto','historico','departamento','deuda_total','deuda_capital',
        'campania','porcentaje','updated_at'
    ];

    public function __construct(int $batchSize = 5000)
    {
        // Lote recomendado 3k–8k en hosting compartido
        $this->batchSize = max(200, $batchSize);
    }

    /**
     * Procesa un CSV en streaming y hace UPSERT por 'codigo'.
     * Devuelve estadísticas básicas.
     */
    public function run(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            throw new \RuntimeException("CSV no encontrado: {$absolutePath}");
        }

        // Menos memoria en inserts masivos
        DB::connection()->disableQueryLog();

        // --- Abrimos y autodetectamos delimitador (coma o ;) ---
        [$delimiter, $headersRaw] = $this->detectDelimiterAndHeaders($absolutePath);

        $fh = new \SplFileObject($absolutePath, 'r');
        $fh->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::DROP_NEW_LINE
        );
        $fh->setCsvControl($delimiter, '"');

        // ---------- Normalización de cabeceras ----------
        $headers = array_map(function ($h) {
            $h = $this->removeBom((string)$h);
            $k = $this->normKey($h);
            // Si en el archivo está '%' como cabecera, la mapeamos
            return ($k === '' && trim($h) === '%') ? 'porcentaje' : $k;
        }, $headersRaw);

        if (!in_array('codigo', $headers, true) || !in_array('dni', $headers, true)) {
            throw new \RuntimeException('Faltan columnas requeridas: codigo y/o dni');
        }

        // Saltamos la primera fila (ya leída en detectDelimiterAndHeaders)
        $fh->rewind(); // volvemos al inicio
        $fh->current(); // headers
        $fh->next();    // posicionar en la primera fila de datos

        // ---------- Loop en streaming ----------
        $processed = 0;
        $inserted  = 0;
        $skipped   = 0;
        $failed    = 0;

        $batch = [];
        $now   = now()->toDateTimeString();

        for (; !$fh->eof(); $fh->next()) {
            $row = $fh->current();

            // Por cómo trabaja SplFileObject, a veces la última iteración es [null]
            if ($row === false || $row === [null] || $row === null) {
                continue;
            }

            $processed++;

            try {
                // Asociar: header[i] => valor[i]
                $assoc = [];
                foreach ($headers as $i => $key) {
                    if ($key === '') continue;
                    // Si faltan columnas en la fila, cae en null
                    $assoc[$key] = $row[$i] ?? null;
                }

                $codigo = trim((string)($assoc['codigo'] ?? ''));
                $dni    = trim((string)($assoc['dni'] ?? ''));

                if ($codigo === '' || $dni === '') {
                    $skipped++;
                    continue;
                }

                $batch[] = [
                    'codigo'        => $codigo,
                    'dni'           => $dni,
                    'titular'       => $this->cut($assoc['titular'] ?? null, 150),
                    'cartera'       => $this->cut($assoc['cartera'] ?? null, 100),
                    'entidad'       => $this->cut($assoc['entidad'] ?? null, 100),
                    'cosecha'       => $this->cut($assoc['cosecha'] ?? null, 100),
                    'sub_cartera'   => $this->cut($assoc['sub_cartera'] ?? ($assoc['subcartera'] ?? null), 100),
                    'producto'      => $this->cut($assoc['producto'] ?? null, 100),
                    'sub_producto'  => $this->cut($assoc['sub_producto'] ?? ($assoc['subproducto'] ?? null), 100),
                    'historico'     => $this->cut($assoc['historico'] ?? null, 120),
                    'departamento'  => $this->cut($assoc['departamento'] ?? null, 100),

                    'deuda_total'   => $this->toDecimal($assoc['deuda_total']   ?? null, 2),
                    'deuda_capital' => $this->toDecimal($assoc['deuda_capital'] ?? null, 2),
                    'campania'      => $this->toDecimal($assoc['campania']      ?? ($assoc['campana'] ?? null), 2),
                    'porcentaje'    => $this->toDecimal($assoc['porcentaje']    ?? null, 9),

                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];

                if (count($batch) >= $this->batchSize) {
                    $this->flush($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        if (!empty($batch)) {
            $this->flush($batch);
            $inserted += count($batch);
        }

        return compact('processed', 'inserted', 'skipped', 'failed');
    }

    // ======================= Internos =======================

    /** Upsert dentro de transacción corta para reducir overhead de autocommit */
    private function flush(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            DB::table('data')->upsert($batch, ['codigo'], $this->updateCols);
        }, 1);
    }

    /** Detecta delimitador (`,` o `;`) y devuelve también la fila de cabeceras */
    private function detectDelimiterAndHeaders(string $path): array
    {
        $h = fopen($path, 'r');
        if ($h === false) {
            throw new \RuntimeException("No se pudo abrir CSV: {$path}");
        }

        $firstNonEmpty = null;
        // intenta leer hasta encontrar una línea no vacía
        for ($i = 0; $i < 5 && !feof($h); $i++) {
            $line = fgets($h);
            if ($line !== false && trim($line) !== '') {
                $firstNonEmpty = $line;
                break;
            }
        }
        if ($firstNonEmpty === null) {
            fclose($h);
            throw new \RuntimeException('CSV vacío.');
        }

        // Conteo simple de separadores
        $cComa = substr_count($firstNonEmpty, ',');
        $cPtoC = substr_count($firstNonEmpty, ';');
        $delimiter = ($cPtoC > $cComa) ? ';' : ',';

        // Parsea cabeceras con ese delimitador
        // Nota: usamos str_getcsv para respetar comillas
        $headersRaw = str_getcsv($this->removeBom($firstNonEmpty), $delimiter, '"');

        fclose($h);
        return [$delimiter, $headersRaw];
    }

    /** Normaliza clave de cabecera a snake sencillo */
    private function normKey(string $k): string
    {
        $k = mb_strtolower($k, 'UTF-8');
        $k = strtr($k, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
        $k = preg_replace('/[^a-z0-9]+/', '_', $k);
        return trim($k, '_');
    }

    private function removeBom(string $s): string
    {
        // quita BOM UTF-8 si existe
        return preg_replace('/^\xEF\xBB\xBF/', '', $s) ?? $s;
    }

    private function cut($v, int $n): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : mb_substr($s, 0, $n);
    }

    /**
     * Convierte "20,853.32" o "20.853,32" a "20853.32" con escala fija.
     */
    private function toDecimal($v, int $scale): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '?' || $s === '-') return null;

        // quita espacios y NBSP
        $s = str_replace(["\xC2\xA0", ' '], '', $s);

        // europeo: miles con punto y decimales con coma
        if (preg_match('/^\-?\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // estilo US/LatAm: miles con coma, decimales con punto
            $s = str_replace(',', '', $s);
        }

        if (!is_numeric($s)) return null;
        return number_format((float)$s, $scale, '.', '');
    }
}
