<?php

namespace App\Services\Expertis;

use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactoryInterface;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyInterface;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\FileBasedStrategy;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\InMemoryStrategy;

class ExpertisSharedStringsCachingStrategyFactory implements CachingStrategyFactoryInterface
{
    private const MAXIMO_EN_MEMORIA = 5000;

    private const STRINGS_POR_ARCHIVO_TEMPORAL = 1000;

    public function createBestCachingStrategy(
        ?int $sharedStringsUniqueCount,
        string $tempFolder,
    ): CachingStrategyInterface {
        if (
            $sharedStringsUniqueCount !== null
            && $sharedStringsUniqueCount <= self::MAXIMO_EN_MEMORIA
        ) {
            return new InMemoryStrategy($sharedStringsUniqueCount);
        }

        return new FileBasedStrategy(
            $tempFolder,
            self::STRINGS_POR_ARCHIVO_TEMPORAL,
        );
    }
}
