<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expertis\GuardarTipificacionExpertisRequest;
use App\Models\TipificacionExpertis;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TipificacionExpertisController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Expertis/Tipificaciones/Index', [
            'tipificaciones' => TipificacionExpertis::query()
                ->withCount('gestiones')
                ->orderBy('peso')
                ->orderBy('tipificacion')
                ->paginate(50),
        ]);
    }

    public function store(
        GuardarTipificacionExpertisRequest $request,
    ): RedirectResponse {
        TipificacionExpertis::create($request->validated());

        return back()->with('ok', 'Tipificación Expertis creada.');
    }

    public function update(
        GuardarTipificacionExpertisRequest $request,
        TipificacionExpertis $tipificacion,
    ): RedirectResponse {
        $tipificacion->update($request->validated());

        return back()->with('ok', 'Tipificación Expertis actualizada.');
    }

    public function destroy(TipificacionExpertis $tipificacion): RedirectResponse
    {
        if ($tipificacion->gestiones()->exists()) {
            $tipificacion->update(['activo' => false]);

            return back()->with(
                'warn',
                'La tipificación tiene gestiones relacionadas y fue desactivada.',
            );
        }

        $tipificacion->delete();

        return back()->with('ok', 'Tipificación Expertis eliminada.');
    }
}
