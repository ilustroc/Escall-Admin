<?php

namespace Tests\Unit;

use App\Support\UploadLimit;
use PHPUnit\Framework\TestCase;

class UploadLimitTest extends TestCase
{
    public function test_convierte_valores_php_en_k_m_y_g_a_bytes(): void
    {
        $this->assertSame(512 * 1024, UploadLimit::parseIniBytes('512K'));
        $this->assertSame(64 * 1024 * 1024, UploadLimit::parseIniBytes('64M'));
        $this->assertSame(1024 * 1024 * 1024, UploadLimit::parseIniBytes('1G'));
        $this->assertNull(UploadLimit::parseIniBytes('-1'));
        $this->assertNull(UploadLimit::parseIniBytes('0'));
    }

    public function test_limite_efectivo_respeta_configuracion_expertis(): void
    {
        $limit = new UploadLimit(50, '64M', '70M');

        $this->assertSame(50 * 1024 * 1024, $limit->maxBytes());
        $this->assertSame(50, $limit->configuration()['max_mb']);
    }

    public function test_limite_efectivo_respeta_upload_max_filesize(): void
    {
        $limit = new UploadLimit(50, '2M', '70M');

        $this->assertSame(2 * 1024 * 1024, $limit->maxBytes());
        $this->assertSame('2M', $limit->configuration()['upload_max_filesize']);
    }

    public function test_post_max_size_reserva_margen_para_multipart(): void
    {
        $limit = new UploadLimit(50, '64M', '8M');

        $this->assertLessThan(8 * 1024 * 1024, $limit->maxBytes());
        $this->assertGreaterThan(7 * 1024 * 1024, $limit->maxBytes());
    }
}
