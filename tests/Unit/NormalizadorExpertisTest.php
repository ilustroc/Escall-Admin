<?php

namespace Tests\Unit;

use App\Services\Expertis\NormalizadorExpertisService;
use PHPUnit\Framework\TestCase;

class NormalizadorExpertisTest extends TestCase
{
    private NormalizadorExpertisService $normalizador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizador = new NormalizadorExpertisService;
    }

    public function test_normaliza_dni_sin_perder_ceros_visibles(): void
    {
        $this->assertSame('09487554', $this->normalizador->dni('09.487.554'));
        $this->assertSame('00001234', $this->normalizador->dni(1234));
    }

    public function test_normaliza_codigo_y_cuenta_para_cruces(): void
    {
        $visible = $this->normalizador->codigoVisible('09487554', ' oh  ');

        $this->assertSame('09487554-OH', $visible);
        $this->assertSame('9487554-OH', $this->normalizador->codigoNormalizado($visible));
    }

    public function test_convierte_fechas_aceptadas(): void
    {
        $this->assertSame('2026-07-21', $this->normalizador->fecha('21/07/2026'));
        $this->assertSame('2026-07-21', $this->normalizador->fecha('2026-07-21'));
        $this->assertNull($this->normalizador->fecha('31/02/2026'));
    }

    public function test_convierte_montos_con_formatos_razonables(): void
    {
        $this->assertSame('200.00', $this->normalizador->monto('S/ 200.00'));
        $this->assertSame('1350.50', $this->normalizador->monto('1.350,50'));
        $this->assertSame('1350.50', $this->normalizador->monto('1,350.50'));
    }

    public function test_genera_hash_estable_sin_campos_de_auditoria(): void
    {
        $gestion = [
            'codigo_normalizado' => '9487554-OH',
            'telefono' => '999888777',
            'fecha_llamada' => '2026-07-21',
            'hora' => '10:15:00',
            'asesor' => 'ANA',
            'campania' => 'JULIO',
            'nivel_1' => 'CONTACTO',
            'nivel_2' => 'PPC',
            'medio_gestion' => 'LLAMADA',
            'created_at' => '2026-07-21 10:00:00',
        ];

        $primero = $this->normalizador->hashGestion($gestion);
        $gestion['created_at'] = '2026-07-22 10:00:00';
        $gestion['numero_fila_origen'] = 999;

        $this->assertSame($primero, $this->normalizador->hashGestion($gestion));
        $this->assertSame(64, strlen($primero));
    }

    public function test_extrae_monto_total_negociado_para_vll(): void
    {
        $this->assertSame(
            '1350.50',
            $this->normalizador->montoProyectado(
                0,
                'vll',
                'Detalle / MONTO TOTAL NEGOCIADO: S/. 1.350,50 / EN DOS CUOTAS',
            ),
        );
        $this->assertSame('0.00', $this->normalizador->montoProyectado(0, 'PPC', 'MONTO TOTAL NEGOCIADO: 900'));
        $this->assertSame('250.00', $this->normalizador->montoProyectado(250, 'VLL', 'MONTO TOTAL NEGOCIADO: 900'));
    }
}
