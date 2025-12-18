<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\AsignacionTecCenterPlaceholderExport;
use App\Exports\ReporteDataCarterasXlsxFastExport;
use App\Exports\ReporteDataTecCenterMesExport;
use App\Exports\ReporteTecCenterMesExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

class ReporteCarterasController extends Controller
{
    public function index(Request $r)
    {
        $tag  = $r->query('tag',  'OCTUBRE25');
        $lote = $r->query('lote', '1809');
        $mes  = $r->query('mes', Carbon::now()->format('Y-m'));
        return view('reportes.carteras', compact('tag', 'lote', 'mes'));
    }

    public function exportDataXlsxFast(Request $r)
    {
        $tag = $r->query('tag', 'DICIEMBRE');
        $mes = $this->assertMonth($r->query('mes'));

        return (new ReporteDataCarterasXlsxFastExport)
            ->forMonth($mes)
            ->stream("REPORTE {$tag} ESCALL.xlsx");
    }

    public function exportAsignacionTec(Request $r)
    {
        $lote = $r->query('lote', '1809');

        return Excel::download(
            new AsignacionTecCenterPlaceholderExport,
            "FRMT_RG AGENCIAS EXTERNAS {$lote}.xlsx"
        );
    }

    public function exportDataTecCenter(Request $r)
    {
        $mes = $this->assertMonth($r->query('mes'));

        return Excel::download(
            (new ReporteDataTecCenterMesExport)->forMonth($mes),
            "REPORTE DATA TEC CENTER {$mes}.xlsx"
        );
    }

    public function exportTecCenterData(Request $r)
    {
        $mes = $this->assertMonth($r->query('mes', Carbon::now()->format('Y-m')));
        $suf = Carbon::createFromFormat('Y-m', $mes)->format('d.m');

        return Excel::download(
            (new ReporteTecCenterMesExport)->forMonth($mes),
            "FRMT_GESTIONES CP - 03.01 ({$suf}).xlsx"
        );
    }

    private function assertMonth(?string $mes): string
    {
        $mes = (string) $mes;
        if (!preg_match('/^\d{4}\-\d{2}$/', $mes)) {
            abort(422, 'Mes inválido. Use formato YYYY-MM.');
        }
        return $mes;
    }
}
