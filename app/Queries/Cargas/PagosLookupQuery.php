<?php

namespace App\Queries\Cargas;

use App\Support\CapitalRange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagosLookupQuery
{
    public function find(string $code): ?array
    {
        $row = Schema::hasTable('data')
            ? DB::table('data')->where('codigo', $code)->first()
            : null;

        if ($row === null) {
            foreach (['asignaciones', 'asignacion', 'asignacion_empresas'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $row = DB::table($table)->where('codigo', $code)->first();
                if ($row !== null) {
                    break;
                }
            }
        }

        if ($row === null) {
            return null;
        }

        $capital = isset($row->deuda_capital) ? (float) $row->deuda_capital : null;

        return [
            'dni' => (string) ($row->dni ?? ''),
            'nombre' => (string) ($row->titular ?? $row->nombre ?? ''),
            'cartera' => (string) ($row->cartera ?? ''),
            'entidad' => (string) ($row->entidad ?? ''),
            'cosecha' => (string) ($row->cosecha ?? ''),
            'departamento' => (string) ($row->departamento ?? ''),
            'rango' => CapitalRange::from($capital),
            'capital' => $capital,
            'producto' => (string) ($row->producto ?? ''),
        ];
    }
}
