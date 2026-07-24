<?php

namespace App\Exports;

use App\Services\Expertis\ExpertisReportQueryService;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpertisPagosExport implements FromQuery, WithCustomChunkSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly array $filtros,
    ) {}

    public function query(): Builder
    {
        return app(ExpertisReportQueryService::class)->pagos($this->filtros);
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'CUENTA',
            'DNI',
            'MONTO',
            'EJECUTIVO',
            'TIPO DE ACUERDO',
            'RECAUDO',
            'CARTERA RELACIONADA',
            'GESTION PREVIA',
            'ARCHIVO ORIGEN',
            'FECHA DE CARGA',
        ];
    }

    public function map($row): array
    {
        return [
            $row->fecha,
            $row->cuenta,
            $row->dni,
            $row->monto,
            $row->ejecutivo,
            $row->tipo_acuerdo,
            $row->recaudo,
            $row->cartera_relacionada,
            $row->tiene_gestion_previa ? 'SI' : 'NO',
            $row->archivo_origen,
            $row->created_at,
        ];
    }

    public function chunkSize(): int
    {
        return (int) config('expertis.chunk_size', 1000);
    }
}
