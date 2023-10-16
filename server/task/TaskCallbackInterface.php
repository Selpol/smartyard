<?php declare(strict_types=1);

namespace Selpol\Task;

interface TaskCallbackInterface
{
    public function __invoke(Task $task);
}