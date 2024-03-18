<?php declare(strict_types=1);

namespace Selpol\Task\Trait;

use Exception;

/**
 * @property int $initialRetry
 */
trait TaskRetryTrait
{
    public int $retry = -1;

    public function retry(?int $delay = null, string $queue = 'low'): bool
    {
        if ($this->retry == -1)
            $this->retry = $this->initialRetry ?? 3;

        if ($this->retry > 0) {
            $this->retry--;

            return task($this)->delay($delay)->queue($queue)->dispatch();
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function retrySync(): bool
    {
        if ($this->retry == -1)
            $this->retry = $this->initialRetry ?? 3;

        if ($this->retry > 0) {
            $this->retry--;

            task($this)->sync();

            return true;
        }

        return false;
    }
}