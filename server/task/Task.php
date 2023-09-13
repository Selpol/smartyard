<?php

namespace Selpol\Task;

use Exception;
use Throwable;

abstract class Task
{
    public ?int $taskId = null;

    public string $title;

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

    protected function setProgress(int|float $progress): void
    {

    }
}