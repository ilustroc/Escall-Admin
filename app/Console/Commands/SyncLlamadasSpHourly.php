<?php

namespace App\Console\Commands;

use App\Services\Cargas\LlamadasSpService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLlamadasSpHourly extends Command
{
    protected $signature = 'llamadas:sync-sp-hourly
                            {--from= : Inicio inclusivo (YYYY-MM-DD HH:MM:SS)}
                            {--to= : Fin exclusivo (YYYY-MM-DD HH:MM:SS)}
                            {--dry-run : Consulta y muestra el mapeo sin borrar ni insertar}';

    protected $description = 'Sincroniza llamadas del discador desde sp_gestiones_nc por intervalos diarios idempotentes.';

    public function handle(LlamadasSpService $llamadas): int
    {
        try {
            [$from, $to] = $this->range($llamadas);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $slices = $this->dailySlices($from, $to);
        $count = count($slices);
        $failed = false;

        if ($dryRun) {
            $this->warn('DRY RUN: no se eliminarán ni insertarán llamadas.');
        }

        foreach ($slices as $index => [$sliceFrom, $sliceTo]) {
            try {
                $stats = $llamadas->sync(
                    $sliceFrom->format('Y-m-d H:i:s'),
                    $sliceTo->format('Y-m-d H:i:s'),
                    $dryRun,
                );
                $prefix = sprintf('[%02d/%02d] %s', $index + 1, $count, $sliceFrom->toDateString());
                $this->info("{$prefix} -> {$stats['obtenidos']} registros; insertados: {$stats['insertados']}; errores: {$stats['errores']}; {$stats['tiempo']} s");

                if ($dryRun && $stats['muestra'] !== []) {
                    $this->table(array_keys($stats['muestra'][0]), $stats['muestra']);
                }
            } catch (\Throwable $e) {
                $failed = true;
                $this->error(sprintf('[%02d/%02d] %s -> falló: %s', $index + 1, $count, $sliceFrom->toDateString(), $e->getMessage()));
                Log::error('Llamadas SP sync failed.', [
                    'from' => $sliceFrom->format('Y-m-d H:i:s'),
                    'to' => $sliceTo->format('Y-m-d H:i:s'),
                    'exception' => $e,
                ]);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(LlamadasSpService $llamadas): array
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (($from === null) !== ($to === null)) {
            throw new \InvalidArgumentException('Debe indicar --from y --to juntos.');
        }

        if ($from === null) {
            $from = now('America/Lima')->startOfDay()->format('Y-m-d H:i:s');
            $to = now('America/Lima')->startOfDay()->addDay()->format('Y-m-d H:i:s');
        }

        return $llamadas->validatedRange((string) $from, (string) $to);
    }

    /** @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}> */
    private function dailySlices(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $slices = [];
        $cursor = $from;

        while ($cursor->lessThan($to)) {
            $next = $cursor->startOfDay()->addDay();
            $sliceTo = $next->lessThan($to) ? $next : $to;
            $slices[] = [$cursor, $sliceTo];
            $cursor = $sliceTo;
        }

        return $slices;
    }
}
