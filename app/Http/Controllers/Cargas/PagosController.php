<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cargas\BuscarPagoRequest;
use App\Http\Requests\Cargas\RegistrarPagoRequest;
use App\Queries\Cargas\PagosLookupQuery;
use App\Services\Cargas\RegistrarPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PagosController extends Controller
{
    public function form(): Response
    {
        return Inertia::render('Cargas/Pagos/Index', [
            'defaults' => ['fecha' => now()->toDateString()],
        ]);
    }

    public function lookup(BuscarPagoRequest $request, PagosLookupQuery $query): JsonResponse
    {
        $record = $query->find($request->validated('codigo'));

        if ($record === null) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el código en DATA/ASIGNACIÓN.',
            ], 404);
        }

        return response()->json(['ok' => true, 'data' => $record]);
    }

    public function store(
        RegistrarPagoRequest $request,
        RegistrarPagoService $service,
    ): RedirectResponse {
        $service->register($request->validated());

        return redirect()->route('cargas.pagos.form')
            ->with('success', 'Pago registrado con el snapshot de la cuenta.');
    }
}
