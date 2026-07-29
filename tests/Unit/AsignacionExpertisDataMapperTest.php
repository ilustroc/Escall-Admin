<?php

namespace Tests\Unit;

use App\Services\Expertis\AsignacionExpertisDataMapper;
use Tests\TestCase;

class AsignacionExpertisDataMapperTest extends TestCase
{
    private AsignacionExpertisDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = app(AsignacionExpertisDataMapper::class);
    }

    public function test_acepta_periodo_numerico_y_periodo_texto(): void
    {
        $this->assertSame('202607', $this->mapper->periodo(202607));
        $this->assertSame('202607', $this->mapper->periodo('202607'));
    }

    public function test_rechaza_periodo_con_formato_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El periodo debe tener el formato YYYYMM con seis dígitos.');

        $this->mapper->periodo('2026.07');
    }

    public function test_rechaza_mes_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'El periodo debe contener un año entre 2000 y 2100 y un mes válido.',
        );

        $this->mapper->periodo('202613');
    }

    public function test_documento_dni_conserva_ceros_y_genera_codigo(): void
    {
        $fila = $this->map($this->fila([
            'documento' => 1,
            'codigo' => '',
            'tipo_cartera' => 'los andes',
        ]));

        $this->assertSame('00000001', $fila['documento']);
        $this->assertSame('00000001-LOS ANDES', $fila['codigo']);
        $this->assertSame('1-LOS ANDES', $fila['codigo_normalizado']);
    }

    public function test_acepta_ruc_y_carnet_de_extranjeria(): void
    {
        $ruc = $this->map($this->fila([
            'documento' => '20123456789',
            'codigo' => '20123456789-LOS ANDES',
        ]));
        $ce = $this->map($this->fila([
            'documento' => 'ce-001234',
            'codigo' => 'CE001234-LOS ANDES',
        ]));

        $this->assertSame('20123456789', $ruc['documento']);
        $this->assertSame('20123456789-LOS ANDES', $ruc['codigo']);
        $this->assertSame('CE001234', $ce['documento']);
        $this->assertSame('CE001234-LOS ANDES', $ce['codigo']);
        $this->assertSame(
            'CE001234-LOS ANDES',
            $ce['codigo_normalizado'],
        );
    }

    public function test_rechaza_documento_vacio_o_demasiado_corto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'El documento debe contener entre 6 y 20 caracteres alfanuméricos.',
        );

        $this->map($this->fila([
            'documento' => 'A-1',
            'codigo' => '',
        ]));
    }

    public function test_rechaza_documento_mayor_a_20_caracteres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'El documento debe contener entre 6 y 20 caracteres alfanuméricos.',
        );

        $this->map($this->fila([
            'documento' => str_repeat('A', 21),
            'codigo' => '',
        ]));
    }

    public function test_acepta_codigo_enviado_cuando_coincide(): void
    {
        $fila = $this->map($this->fila([
            'codigo' => '00000001-los andes',
        ]));

        $this->assertSame('00000001-LOS ANDES', $fila['codigo']);
    }

    public function test_rechaza_codigo_enviado_cuando_no_coincide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'El código no coincide con el documento y el tipo de cartera para Expertis.',
        );

        $this->map($this->fila(['codigo' => '00000001-PRO']));
    }

    public function test_convierte_guion_null_y_espacios_a_null_solo_en_campos_opcionales(): void
    {
        $fila = $this->map($this->fila([
            'cosecha' => '-',
            'sub_cosecha' => ' NULL ',
            'producto' => '   ',
            'sueldo' => '-',
            'campania' => '',
        ]));

        $this->assertNull($fila['cosecha']);
        $this->assertNull($fila['sub_cosecha']);
        $this->assertNull($fila['producto']);
        $this->assertNull($fila['sueldo']);
        $this->assertNull($fila['campania']);
    }

    public function test_acepta_fecha_nativa_y_fecha_dia_mes_anio(): void
    {
        $nativa = $this->map($this->fila([
            'fecha_nacimiento' => new \DateTimeImmutable('1999-04-25'),
        ]));
        $texto = $this->map($this->fila([
            'fecha_nacimiento' => '25/04/1999',
        ]));

        $this->assertSame('1999-04-25', $nativa['fecha_nacimiento']);
        $this->assertSame('1999-04-25', $texto['fecha_nacimiento']);
    }

    public function test_normaliza_montos_con_punto_y_coma(): void
    {
        $fila = $this->map($this->fila([
            'deuda_total' => '2,994.71',
            'deuda_capital' => '1.190,85',
            'campania' => 'S/ 476.34',
        ]));

        $this->assertSame('2994.71', $fila['deuda_total']);
        $this->assertSame('1190.85', $fila['deuda_capital']);
        $this->assertSame('476.34', $fila['campania']);
    }

    public function test_deudas_obligatorias_no_convierten_vacio_en_cero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La deuda total es obligatoria y debe tener un formato válido.',
        );

        $this->map($this->fila(['deuda_total' => '-']));
    }

    public function test_porcentaje_conserva_valor_sin_escalarlo(): void
    {
        $decimal = $this->map($this->fila(['porcentaje' => 0.125]));
        $texto = $this->map($this->fila(['porcentaje' => '12,3456%']));

        $this->assertSame('0.1250', $decimal['porcentaje']);
        $this->assertSame('12.3456', $texto['porcentaje']);
    }

    public function test_acepta_sexo_m_y_f(): void
    {
        $this->assertSame('M', $this->map($this->fila(['sexo' => 'm']))['sexo']);
        $this->assertSame('F', $this->map($this->fila(['sexo' => 'F']))['sexo']);
    }

    public function test_rechaza_sexo_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El sexo debe ser M o F.');

        $this->map($this->fila(['sexo' => 'X']));
    }

    public function test_valida_rangos_de_enteros_opcionales(): void
    {
        $fila = $this->map($this->fila([
            'edad' => 0,
            'entidades' => 0,
            'anio_laboral' => 1900,
            'anio_castigo' => 2100,
        ]));

        $this->assertSame(0, $fila['edad']);
        $this->assertSame(0, $fila['entidades']);
        $this->assertSame(1900, $fila['anio_laboral']);
        $this->assertSame(2100, $fila['anio_castigo']);
    }

    public function test_hash_no_cambia_por_campos_de_auditoria(): void
    {
        $primera = $this->map($this->fila(), 10, 2);
        $segunda = $this->map($this->fila(), 99, 400);

        $this->assertSame($primera['hash_fila'], $segunda['hash_fila']);
    }

    private function map(array $fila, ?int $importacion = null, ?int $numero = null): array
    {
        return $this->mapper->map($fila, $importacion, $numero);
    }

    private function fila(array $cambios = []): array
    {
        return array_merge([
            'periodo' => '202607',
            'empresa' => 'EXPERTIS',
            'documento' => '00000001',
            'titular' => 'CLIENTE FICTICIO',
            'codigo' => '00000001-LOS ANDES',
            'tipo_cartera' => 'LOS ANDES',
            'cosecha' => 'COSECHA 2026',
            'sub_cosecha' => '-',
            'producto' => 'S4',
            'sub_producto' => 'B',
            'historico' => 'JULIO 2026',
            'departamento' => 'PUNO',
            'deuda_total' => 2994.71,
            'deuda_capital' => 1190.85,
            'campania' => 476.34,
            'porcentaje' => 0,
            'fecha_nacimiento' => '25/04/1999',
            'edad' => 27,
            'entidades' => 1,
            'negocio' => 'NO',
            'sueldo' => '-',
            'situacion_laboral' => '-',
            'anio_laboral' => '-',
            'sexo' => 'F',
            'rango_sueldo' => '-',
            'anio_castigo' => 2021,
        ], $cambios);
    }
}
