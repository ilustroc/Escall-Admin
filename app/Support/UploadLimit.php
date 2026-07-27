<?php

namespace App\Support;

final class UploadLimit
{
    private const BYTES_PER_KILOBYTE = 1024;

    private const BYTES_PER_MEGABYTE = 1048576;

    private const MINIMUM_MULTIPART_MARGIN = 65536;

    private const MAXIMUM_MULTIPART_MARGIN = 1048576;

    public function __construct(
        private readonly ?int $configuredMaxMb = null,
        private readonly ?string $uploadMaxFilesize = null,
        private readonly ?string $postMaxSize = null,
    ) {}

    public static function parseIniBytes(string|int|float|false|null $value): ?int
    {
        if ($value === false || $value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (! preg_match('/^([+-]?\d+(?:\.\d+)?)\s*([kmgt]?)b?$/i', $normalized, $matches)) {
            return null;
        }

        $amount = (float) $matches[1];
        if ($amount <= 0) {
            return null;
        }

        $multiplier = match (strtoupper($matches[2])) {
            'K' => self::BYTES_PER_KILOBYTE,
            'M' => self::BYTES_PER_MEGABYTE,
            'G' => self::BYTES_PER_MEGABYTE * self::BYTES_PER_KILOBYTE,
            'T' => self::BYTES_PER_MEGABYTE * self::BYTES_PER_MEGABYTE,
            default => 1,
        };

        return (int) floor($amount * $multiplier);
    }

    public function maxBytes(): int
    {
        $limits = array_filter([
            $this->configuredMaxBytes(),
            self::parseIniBytes($this->rawUploadMaxFilesize()),
            $this->postPayloadLimitBytes(),
        ], static fn (?int $limit): bool => $limit !== null && $limit > 0);

        return $limits === [] ? 0 : min($limits);
    }

    public function maxKilobytes(): int
    {
        $maxBytes = $this->maxBytes();

        return $maxBytes > 0
            ? max(1, (int) floor($maxBytes / self::BYTES_PER_KILOBYTE))
            : PHP_INT_MAX;
    }

    public function configuration(): array
    {
        $maxBytes = $this->maxBytes();
        $maxMb = $maxBytes > 0
            ? round($maxBytes / self::BYTES_PER_MEGABYTE, 2)
            : 0;

        return [
            'max_mb' => floor($maxMb) === $maxMb ? (int) $maxMb : $maxMb,
            'max_bytes' => $maxBytes,
            'configured_max_mb' => $this->configuredMaxMb(),
            'upload_max_filesize' => $this->rawUploadMaxFilesize(),
            'post_max_size' => $this->rawPostMaxSize(),
            'extensiones' => ['xlsx'],
        ];
    }

    private function configuredMaxMb(): int
    {
        return $this->configuredMaxMb
            ?? max(0, (int) config('expertis.import_max_mb', 50));
    }

    private function configuredMaxBytes(): ?int
    {
        $maxMb = $this->configuredMaxMb();

        return $maxMb > 0 ? $maxMb * self::BYTES_PER_MEGABYTE : null;
    }

    private function rawUploadMaxFilesize(): string
    {
        return $this->uploadMaxFilesize
            ?? (string) ini_get('upload_max_filesize');
    }

    private function rawPostMaxSize(): string
    {
        return $this->postMaxSize
            ?? (string) ini_get('post_max_size');
    }

    private function postPayloadLimitBytes(): ?int
    {
        $postMaxBytes = self::parseIniBytes($this->rawPostMaxSize());
        if ($postMaxBytes === null) {
            return null;
        }

        $margin = (int) ceil($postMaxBytes * 0.02);
        $margin = max(self::MINIMUM_MULTIPART_MARGIN, $margin);
        $margin = min(self::MAXIMUM_MULTIPART_MARGIN, $margin);

        return max(1, $postMaxBytes - $margin);
    }
}
