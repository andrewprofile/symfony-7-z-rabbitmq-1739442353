<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;

final class ComputeProgressService
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function compute(string $fileName, int $currentRow, int $totalRows): void
    {
        $progress = ceil(($currentRow * 100) / $totalRows);
        $item = $this->cache->getItem('progress_'.$fileName);
        $item->set($progress);
        $item->expiresAfter(60);
        $this->cache->save($item);
    }
}