<?php declare(strict_types=1);

namespace Selpol\Task;

use Exception;
use Throwable;

abstract class Task
{
    public string $title;

    public int $retry = 0;

    private mixed $progressCallback = null;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public abstract function onTask(): mixed;

    public function onError(Throwable $throwable): void
    {
    }

    public function setProgressCallback(?callable $callback): void
    {
        if ($callback)
            $this->progressCallback = $callback;
        else unset($this->progressCallback);
    }

    protected function retryLow(?int $delay = null): bool
    {
        if ($this->retry > 0) {
            $this->retry--;

            return task($this)->delay($delay)->low()->dispatch();
        }

        return false;
    }

    protected function setProgress(int|float $progress): void
    {
        if (isset($this->progressCallback) && $this->progressCallback)
            call_user_func($this->progressCallback, $progress);
    }
}