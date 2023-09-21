<?php

namespace Selpol\Task;

use Exception;
use Throwable;

abstract class Task
{
    public string $title;

    public int $retry = 0;

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

    }
}