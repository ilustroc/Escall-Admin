<?php
// app/Http/Controllers/Cargas/PagosController.php
namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PagosController extends Controller
{
    public function form(): View
    {
        return view('cargas.pagos'); // vista dedicada (abajo te dejo el Blade)
    }

    // AJAX: trae snapshot por CODIGO desde DATA (fallback: ASIGNACION si existe)
    public function lookup(Request $r)
    {
        $codigo = trim((string)$r->query('codigo', ''));

        if ($codigo === '') {
            return response()->json(['ok' => false, 'msg' => 'Falta el código.'], 422);
        }

        // 1) DATA
        $row = DB::table('data')->where('codigo', $codigo)->first();

        // 2) Fallback ASIGNACION si existe alguna tabla conocida (opcional)
        if (!$row) {
            foreach (['asignaciones', 'asignacion', 'asignacion_empresas'] as $tbl) {
                if (Schema::hasTable($tbl)) {
                    $row = DB::table($tbl)->where('codigo', $codigo)->first();
                    if ($row) break;
                }
            }
        }

        if (!$row) {
            return response()->json(['ok' => false, 'msg' => 'No se encontró el código en DATA/ASIGNACIÓN.'], 404);
        }

        // Campos esperados desde DATA
        $dni          = (string)($row->dni ?? '');
        $nombre       = (string)($row->titular ?? $row->nombre ?? '');
        $cartera      = (string)($row->cartera ?? '');
        $entidad      = (string)($row->entidad ?? '');
        $cosecha      = (string)($row->cosecha ?? '');
        $departamento = (string)($row->departamento ?? '');
        $producto     = (string)($row->producto ?? '');
        $capital      = is_null($row->deuda_capital ?? null) ? null : (float)$row->deuda_capital;

        $rango = $this->rango($capital);

        return response()->json([
            'ok' => true,
            'data' => [
                'dni'          => $dni,
                'nombre'       => $nombre,
                'cartera'      => $cartera,
                'entidad'      => $entidad,
                'cosecha'      => $cosecha,
                'departamento' => $departamento,
                'rango'        => $rango,
                'capital'      => $capital,
                'producto'     => $producto,
            ]
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $v = $r->validate([
            'codigo'    => ['required','string','max:30'],
            'asesor'    => ['nullable','string','max:100'],
            'fecha'     => ['required','date'],
            'monto'     => ['required','numeric','min:0'],
            'operacion' => ['required','in:CANCELACION,PAGO PARCIAL,CUOTA'],

            // snapshot (relleno desde el lookup o manual si quiere)
            'dni'          => ['nullable','string','max:15'],
            'nombre'       => ['nullable','string','max:150'],
            'cartera'      => ['nullable','string','max:100'],
            'entidad'      => ['nullable','string','max:100'],
            'cosecha'      => ['nullable','string','max:100'],
            'departamento' => ['nullable','string','max:100'],
            'rango'        => ['nullable','string','max:30'],
            'capital'      => ['nullable','numeric'],
            'producto'     => ['nullable','string','max:100'],
        ]);

        // Seguridad: si viene capital sin rango, lo recalculamos
        if (empty($v['rango'])) {
            $v['rango'] = $this->rango($v['capital'] ?? null);
        }

        DB::table('pagos')->insert([
            'codigo'       => (string)$v['codigo'],
            'asesor'       => $v['asesor'] ?? null,
            'fecha'        => $v['fecha'],
            'monto'        => $v['monto'],
            'operacion'    => $v['operacion'],

            'dni'          => $v['dni'] ?? null,
            'nombre'       => $v['nombre'] ?? null,
            'cartera'      => $v['cartera'] ?? null,
            'entidad'      => $v['entidad'] ?? null,
            'cosecha'      => $v['cosecha'] ?? null,
            'departamento' => $v['departamento'] ?? null,
            'rango'        => $v['rango'] ?? null,
            'capital'      => $v['capital'] ?? null,
            'producto'     => $v['producto'] ?? null,

            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('ok', 'Pago registrado con snapshot de datos.');
    }

    private function rango($capital): string
    {
        $c = (float)($capital ?? 0);
        return $c >= 50000 ? '1.[50K - MAS]' :
               ($c >= 20000 ? '2.[20K - 50K]' :
               ($c >= 10000 ? '3.[10K - 20K]' :
               ($c >=  5000 ? '4.[5K - 10K]' :
               ($c >=  1000 ? '5.[1K - 5K]'  :
               ($c >=   501 ? '6.[501 - 1K]' : '7.[0 - 500]')))));
    }
}
