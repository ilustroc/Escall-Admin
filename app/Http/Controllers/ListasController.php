<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pago;
use App\Models\Gestion;
use App\Models\Data;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListasController extends Controller
{
    private function getListas() {
        return [
            'asesores'   => Pago::distinct()->whereNotNull('asesor')->orderBy('asesor')->pluck('asesor'),
            'cosechas'   => Data::distinct()->whereNotNull('cosecha')->orderBy('cosecha')->pluck('cosecha'),
            'carteras'   => Data::distinct()->whereNotNull('cartera')->orderBy('cartera')->pluck('cartera'),
            'resultados' => Gestion::distinct()->whereNotNull('tipificacion')->orderBy('tipificacion')->pluck('tipificacion'),
        ];
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'pagos');
        $listas = $this->getListas();

        // Aplicamos la lógica común
        $query = $this->getQueryForTab($tab, $request);
        
        // Paginamos
        $data = $query->paginate(15)->appends($request->all());

        return view('listas.index', compact('tab', 'data', 'listas'));
    }

    public function export(Request $request)
    {
        $tab = $request->input('tab', 'pagos');
        $fileName = 'reporte_' . $tab . '_' . date('Ymd') . '.csv';

        // 1. Reutilizamos la misma query con filtros
        $query = $this->getQueryForTab($tab, $request);

        $response = new StreamedResponse(function() use ($query) {
            $handle = fopen('php://output', 'w');
            
            // BOM para que Excel reconozca tildes y ñ
            fputs($handle, "\xEF\xBB\xBF");

            $headersPrinted = false;

            // 2. Procesamos por lotes (Chunks)
            $query->chunk(1000, function($rows) use ($handle, &$headersPrinted) {
                foreach ($rows as $row) {
                    
                    // AQUI ESTA LA MAGIA: Ocultamos lo que no queremos
                    $row->makeHidden(['created_at', 'updated_at']); // (id no existe)

                    // Convertimos el objeto a array (Clave => Valor)
                    $data = $row->toArray();

                    // 3. Imprimir Encabezados (Solo la primera vez)
                    if (!$headersPrinted) {
                        fputcsv($handle, array_keys($data));
                        $headersPrinted = true;
                    }

                    // 4. Imprimir la fila de datos
                    fputcsv($handle, $data);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    // --- LÓGICA CENTRAL DE FILTROS ---
    private function getQueryForTab($tab, Request $request)
    {
        if ($tab === 'pagos') {
            $q = Pago::orderBy('fecha', 'desc');
            
            // Filtros específicos
            if ($request->filled('dni'))       $q->where('dni', 'like', "%{$request->dni}%");
            
            // Lógica Rango de Fechas
            if ($request->filled('fecha_ini')) $q->whereDate('fecha', '>=', $request->fecha_ini);
            if ($request->filled('fecha_fin')) $q->whereDate('fecha', '<=', $request->fecha_fin);

            if ($request->filled('cosecha'))   $q->where('cosecha', $request->cosecha);
            if ($request->filled('asesor'))    $q->where('asesor', $request->asesor);
            
            return $q;
        } 
        
        elseif ($tab === 'gestiones') {
            $q = Gestion::orderBy('fecha_gestion', 'desc');

            if ($request->filled('dni'))        $q->where('dni', 'like', "%{$request->dni}%");
            
            // Lógica Rango de Fechas
            if ($request->filled('fecha_ini'))  $q->whereDate('fecha_gestion', '>=', $request->fecha_ini);
            if ($request->filled('fecha_fin'))  $q->whereDate('fecha_gestion', '<=', $request->fecha_fin);

            if ($request->filled('resultado'))  $q->where('tipificacion', $request->resultado);
            if ($request->filled('asesor'))     $q->where('nombre', $request->asesor);

            if (!$request->anyFilled(['dni', 'fecha_ini', 'fecha_fin', 'resultado', 'asesor'])) {
                $q->whereMonth('fecha_gestion', Carbon::now()->month)
                  ->whereYear('fecha_gestion', Carbon::now()->year);
            }
            return $q;
        }
        
        elseif ($tab === 'data') {
            $q = Data::query(); // Data no suele tener fecha de registro para filtrar rango, se queda igual
            if ($request->filled('dni'))      $q->where('dni', 'like', "%{$request->dni}%");
            if ($request->filled('cartera'))  $q->where('cartera', $request->cartera);
            if ($request->filled('cosecha'))  $q->where('cosecha', $request->cosecha);
            return $q;
        }
    }

    public function update(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type');

        try {
            if ($type == 'pago') {
                $p = Pago::findOrFail($id);
                // Actualizamos todo lo que venga
                $p->fill($request->only(['fecha', 'monto', 'cosecha', 'asesor', 'operacion']));
                $p->save();
            }
            elseif ($type == 'gestion') {
                $g = Gestion::findOrFail($id);
                
                // Mapeo manual porque los nombres del form pueden variar de la BD
                $g->tipificacion = $request->input('resultado');
                $g->observacion = $request->input('observacion');
                
                // IMPORTANTE: Guardar cambio de Asesor y Fecha
                if ($request->filled('asesor')) {
                    $g->nombre = $request->input('asesor'); // En tabla gestiones el asesor es 'nombre'
                }
                if ($request->filled('fecha_gestion')) {
                    $g->fecha_gestion = $request->input('fecha_gestion');
                }
                
                $g->save();
            }
            elseif ($type == 'data') {
                // Buscamos por ID o respaldo por DNI
                $codigo = $request->input('id'); // aquí "id" será el codigo
                $d = Data::findOrFail($codigo);

                $d->update($request->only(['cartera', 'cosecha', 'titular', 'deuda_capital']));
            }

            return back()->with('ok', 'Registro actualizado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type');

        if ($type == 'pago') Pago::destroy($id);
        if ($type == 'gestion') Gestion::destroy($id);
        if ($type == 'data') { Data::destroy($id);}

        return back()->with('ok', 'Registro eliminado.');
    }

}