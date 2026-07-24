<?php

namespace App\Http\Controllers\Tablas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tablas\FiltrarTablaRequest;
use App\Queries\Tablas\DataTableQuery;
use App\Queries\Tablas\GestionesTableQuery;
use App\Queries\Tablas\PagosTableQuery;
use Inertia\Inertia;
use Inertia\Response;

class TablasController extends Controller
{
    public function index(
        FiltrarTablaRequest $request,
        GestionesTableQuery $managements,
        PagosTableQuery $payments,
        DataTableQuery $portfolio,
    ): Response {
        $filters = $request->validated();
        $result = match ($filters['tab']) {
            'gestiones' => $managements->get($filters),
            'pagos' => $payments->get($filters),
            'data' => $portfolio->get($filters),
        };

        return Inertia::render('Tablas/Index', [
            'tab' => $filters['tab'],
            'filtros' => $filters,
            'resultado' => $result,
        ]);
    }
}
