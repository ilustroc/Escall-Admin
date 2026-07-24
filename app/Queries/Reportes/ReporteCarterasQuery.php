<?php

namespace App\Queries\Reportes;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporteCarterasQuery
{
    public function summary(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endExclusive = Carbon::createFromFormat('Y-m', $month)->addMonth()->startOfMonth()->toDateString();

        return [
            'clientes' => Schema::hasTable('data') ? DB::table('data')->count() : 0,
            'gestiones_mes' => Schema::hasTable('gestiones')
                ? DB::table('gestiones')
                    ->where('fecha_gestion', '>=', $start)
                    ->where('fecha_gestion', '<', $endExclusive)
                    ->count()
                : 0,
            'carteras' => Schema::hasTable('data')
                ? DB::table('data')
                    ->whereNotNull('cartera')
                    ->distinct()
                    ->orderBy('cartera')
                    ->pluck('cartera')
                : collect(),
        ];
    }
}
