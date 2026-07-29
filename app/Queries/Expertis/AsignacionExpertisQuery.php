<?php

namespace App\Queries\Expertis;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AsignacionExpertisQuery
{
    public function filtrosConPeriodoPredeterminado(array $filtros): array
    {
        if (($filtros['periodo'] ?? '') === '') {
            $filtros['periodo'] = $this->ultimoPeriodo();
        }

        return $filtros;
    }

    public function query(array $filtros, bool $ordenar = true): Builder
    {
        $query = DB::table('asignaciones_expertis')
            ->select([
                'id',
                'periodo',
                'empresa',
                'documento',
                'titular',
                'codigo',
                'codigo_normalizado',
                'tipo_cartera',
                'cosecha',
                'sub_cosecha',
                'producto',
                'sub_producto',
                'historico',
                'departamento',
                'deuda_total',
                'deuda_capital',
                'campania',
                'porcentaje',
                'fecha_nacimiento',
                'edad',
                'entidades',
                'negocio',
                'sueldo',
                'situacion_laboral',
                'anio_laboral',
                'sexo',
                'rango_sueldo',
                'anio_castigo',
                'importacion_expertis_id',
                'numero_fila_origen',
                'created_at',
                'updated_at',
            ])
            ->when($filtros['periodo'] ?? null, fn ($q, $valor) => $q->where('periodo', $valor))
            ->when(
                $filtros['documento'] ?? $filtros['dni'] ?? null,
                fn ($q, $valor) => $q->where(
                    'documento',
                    'like',
                    '%'.$valor.'%',
                ),
            )
            ->when($filtros['codigo'] ?? null, fn ($q, $valor) => $q->where('codigo', 'like', '%'.$valor.'%'))
            ->when($filtros['titular'] ?? null, fn ($q, $valor) => $q->where('titular', 'like', '%'.$valor.'%'))
            ->when($filtros['tipo_cartera'] ?? null, fn ($q, $valor) => $q->where('tipo_cartera', $valor))
            ->when($filtros['departamento'] ?? null, fn ($q, $valor) => $q->where('departamento', $valor))
            ->when($filtros['producto'] ?? null, fn ($q, $valor) => $q->where('producto', $valor));

        if ($ordenar) {
            $query->orderByDesc('periodo')->orderBy('codigo_normalizado')->orderBy('id');
        }

        return $query;
    }

    public function opciones(): array
    {
        $distintos = function (string $columna): array {
            return DB::table('asignaciones_expertis')
                ->whereNotNull($columna)
                ->where($columna, '<>', '')
                ->distinct()
                ->orderBy($columna)
                ->limit(250)
                ->pluck($columna)
                ->all();
        };

        return [
            'periodos' => DB::table('asignaciones_expertis')
                ->distinct()
                ->orderByDesc('periodo')
                ->pluck('periodo')
                ->all(),
            'tipos_cartera' => $distintos('tipo_cartera'),
            'departamentos' => $distintos('departamento'),
            'productos' => $distintos('producto'),
        ];
    }

    private function ultimoPeriodo(): ?string
    {
        return DB::table('asignaciones_expertis')->max('periodo');
    }
}
