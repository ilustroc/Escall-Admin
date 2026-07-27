<?php

namespace App\Exports;

use App\Queries\Expertis\AsignacionExpertisQuery;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpertisAsignacionesExport
{
    public function __construct(
        private readonly AsignacionExpertisQuery $asignaciones,
    ) {}

    public function download(array $filtros): BinaryFileResponse
    {
        $temporal = tempnam(sys_get_temp_dir(), 'expertis_assignments_export_');
        if ($temporal === false) {
            throw new RuntimeException('No se pudo preparar la exportación XLSX.');
        }

        $ruta = $temporal.'.xlsx';

        try {
            if (! rename($temporal, $ruta)) {
                throw new RuntimeException('No se pudo preparar la exportación XLSX.');
            }

            $writer = new Writer;
            $writer->openToFile($ruta);
            $writer->addRow(Row::fromValues([
                'PERIODO',
                'EMPRESA',
                'DNI',
                'TITULAR',
                'CODIGO',
                'TIPO DE CARTERA',
                'COSECHA',
                'SUB COSECHA',
                'PRODUCTO',
                'SUB_PRODUCTO',
                'HISTORICO',
                'DEPARTAMENTO',
                'DEUDA TOTAL',
                'DEUDA CAPITAL',
                'CAMPAÑA',
                '%',
                'AÑO_NACIMIENTO',
                'EDAD',
                'ENTIDADES',
                'NEGOCIO',
                'SUELDO',
                'SITUACION_LABORAL',
                'AÑO_LABORAL',
                'SEXO',
                'RANGO_SUELDO',
                'AÑO_CASTIGO',
            ]));

            foreach ($this->asignaciones->query($filtros)->cursor() as $fila) {
                $writer->addRow(Row::fromValues([
                    (string) $fila->periodo,
                    $fila->empresa,
                    (string) $fila->dni,
                    $fila->titular,
                    (string) $fila->codigo,
                    $fila->tipo_cartera,
                    $fila->cosecha,
                    $fila->sub_cosecha,
                    $fila->producto,
                    $fila->sub_producto,
                    $fila->historico,
                    $fila->departamento,
                    $this->numero($fila->deuda_total),
                    $this->numero($fila->deuda_capital),
                    $this->numero($fila->campania),
                    $this->numero($fila->porcentaje),
                    $this->fecha($fila->fecha_nacimiento),
                    $fila->edad === null ? null : (int) $fila->edad,
                    $fila->entidades === null ? null : (int) $fila->entidades,
                    $fila->negocio,
                    $this->numero($fila->sueldo),
                    $fila->situacion_laboral,
                    $fila->anio_laboral === null ? null : (int) $fila->anio_laboral,
                    $fila->sexo,
                    $fila->rango_sueldo,
                    $fila->anio_castigo === null ? null : (int) $fila->anio_castigo,
                ]));
            }

            $writer->close();
        } catch (\Throwable $exception) {
            if (isset($writer)) {
                try {
                    $writer->close();
                } catch (\Throwable) {
                }
            }

            if (is_file($ruta)) {
                unlink($ruta);
            }
            if (is_file($temporal)) {
                unlink($temporal);
            }

            throw $exception;
        }

        return response()
            ->download(
                $ruta,
                'asignaciones_expertis_'.now()->format('Ymd_His').'.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            )
            ->deleteFileAfterSend(true);
    }

    private function numero(mixed $valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    private function fecha(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $partes = explode('-', substr($valor, 0, 10));

        return count($partes) === 3
            ? implode('/', array_reverse($partes))
            : $valor;
    }
}
