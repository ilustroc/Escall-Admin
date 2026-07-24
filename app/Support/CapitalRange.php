<?php

namespace App\Support;

final class CapitalRange
{
    public static function from(mixed $capital): string
    {
        $value = (float) ($capital ?? 0);

        return match (true) {
            $value >= 50000 => '1.[50K - MAS]',
            $value >= 20000 => '2.[20K - 50K]',
            $value >= 10000 => '3.[10K - 20K]',
            $value >= 5000 => '4.[5K - 10K]',
            $value >= 1000 => '5.[1K - 5K]',
            $value >= 501 => '6.[501 - 1K]',
            default => '7.[0 - 500]',
        };
    }
}
