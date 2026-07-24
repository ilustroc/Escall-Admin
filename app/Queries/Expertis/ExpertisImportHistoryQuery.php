<?php

namespace App\Queries\Expertis;

use App\Models\ImportacionExpertis;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpertisImportHistoryQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return ImportacionExpertis::query()
            ->with('usuario:id,name,email')
            ->when($filters['tipo'] ?? null, fn ($query, $value) => $query->where('tipo', $value))
            ->when($filters['estado'] ?? null, fn ($query, $value) => $query->where('estado', $value))
            ->when($filters['desde'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['hasta'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();
    }

    public function selected(ImportacionExpertis $import): ImportacionExpertis
    {
        $import->load('usuario:id,name,email');
        $import->setRelation(
            'errores',
            $import->errores()->orderBy('numero_fila')->limit(100)->get(),
        );

        return $import;
    }
}
