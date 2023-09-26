<?php declare(strict_types=1);

namespace Selpol\Task;

interface TaskCallback
{
    public function __invoke(Task $task);
}