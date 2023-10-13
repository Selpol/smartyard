<?php declare(strict_types=1);

namespace Selpol\Task;

interface TaskCallbackInterface
{
    public function task(Task $task);
}