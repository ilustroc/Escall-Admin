<?php

namespace App\Exports;

use App\Models\Data;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AsignacionTecCenterExport implements FromQuery, WithCustomChunkSize, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Data::query()
            ->where('cartera', 'TEC CENTER')
            ->select([
                'codigo',
                'dni',
                'titular',
                'cartera',
                'entidad',
                'cosecha',
                'producto',
                'departamento',
                'deuda_total',
                'deuda_capital',
                'campania',
                'porcentaje',
            ])
            ->orderBy('codigo');
    }

    public function headings(): array
    {
        return [
            'CODIGO',
            'DNI',
            'TITULAR',
            'CARTERA',
            'ENTIDAD',
            'COSECHA',
            'PRODUCTO',
            'DEPARTAMENTO',
            'DEUDA TOTAL',
            'DEUDA CAPITAL',
            'CAMPANIA',
            'PORCENTAJE',
        ];
    }

    public function map($row): array
    {
        return [
            $row->codigo,
            $row->dni,
            $row->titular,
            $row->cartera,
            $row->entidad,
            $row->cosecha,
            $row->producto,
            $row->departamento,
            $row->deuda_total,
            $row->deuda_capital,
            $row->campania,
            $row->porcentaje,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
