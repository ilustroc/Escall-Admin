<?php

namespace App\Exports;

use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpertisGestionesExport implements FromQuery, WithCustomChunkSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        return app(ExpertisReportQueryService::class)->gestiones($this->filtros);
    }

    public function headings(): array
    {
        return [
            'CODIGO',
            'DNI',
            'CARTERA',
            'ASESOR',
            'EQUIPO',
            'TELEFONO',
            'FECHA LLAMADA',
            'HORA',
            'NIVEL 1',
            'NIVEL 2',
            'PESO',
            'FECHA COMPROMISO',
            'MONTO',
            'ESTADO',
            'FECHA PAGADA',
            'MONTO PAGADO',
            'PS-PROYECTADO',
            'UNICO',
            'NOMBRE CLIENTE',
            'CAMPANA',
            'OBSERVACION',
            'MEDIO GESTION',
        ];
    }

    public function map($row): array
    {
        return [
            $row->codigo,
            $row->dni,
            $row->cartera,
            $row->asesor,
            $row->equipo,
            $row->telefono,
            $row->fecha_llamada,
            $row->hora,
            $row->nivel_1,
            $row->nivel_2,
            $row->peso,
            $row->fecha_compromiso,
            $row->monto,
            $row->estado,
            $row->fecha_pagada,
            $row->monto_pagado,
            $row->ps_proyectado,
            $row->unico,
            $row->nombre_cliente,
            $row->campania,
            $row->observacion,
            $row->medio_gestion,
        ];
    }

    public function chunkSize(): int
    {
        return (int) config('expertis.chunk_size', 1000);
    }
}
