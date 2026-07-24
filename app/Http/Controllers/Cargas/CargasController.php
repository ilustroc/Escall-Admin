<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cargas\CargasIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

class CargasController extends Controller
{
    public function index(CargasIndexRequest $request): Response
    {
        return match ($request->validated('tab')) {
            'gestiones' => Inertia::render('Cargas/GestionesSp/Index', [
                'filtros' => [
                    'fi' => now()->startOfMonth()->toDateString(),
                    'ff' => now()->toDateString(),
                ],
                'preview' => null,
            ]),
            'data' => Inertia::render('Cargas/Data/Index'),
            'pagos' => Inertia::render('Cargas/Pagos/Index', [
                'defaults' => ['fecha' => now()->toDateString()],
            ]),
        };
    }
}
