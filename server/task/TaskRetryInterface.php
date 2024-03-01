<?php declare(strict_types=1);

namespace Selpol\Task;

interface TaskRetryInterface
{
    public function retry(?int $delay = null, string $queue = 'low'): bool;

    public function retrySync(): bool;
}