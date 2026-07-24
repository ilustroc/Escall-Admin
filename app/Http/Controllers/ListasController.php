<?php

namespace App\Http\Controllers;

use App\Exports\ListasCsvExport;
use App\Http\Requests\Listas\ActualizarListaRequest;
use App\Http\Requests\Listas\EliminarListaRequest;
use App\Http\Requests\Listas\FiltrarListaRequest;
use App\Queries\Listas\ListasQuery;
use App\Services\Listas\ActualizarListaService;
use App\Services\Listas\EliminarListaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListasController extends Controller
{
    public function index(FiltrarListaRequest $request, ListasQuery $query): Response
    {
        $filters = $request->validated();

        return Inertia::render('Listas/Index', [
            'tab' => $filters['tab'],
            'filtros' => $filters,
            'registros' => $query->paginate($filters),
            'opciones' => $query->options(),
        ]);
    }

    public function export(
        FiltrarListaRequest $request,
        ListasQuery $query,
        ListasCsvExport $export,
    ): StreamedResponse {
        $filters = $request->validated();

        return $export->download($query->builder($filters), $filters['tab']);
    }

    public function update(
        ActualizarListaRequest $request,
        ActualizarListaService $service,
    ): RedirectResponse {
        $service->update($request->validated());

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(
        EliminarListaRequest $request,
        EliminarListaService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->delete($data['type'], $data['id']);

        return back()->with('success', 'Registro eliminado.');
    }
}
