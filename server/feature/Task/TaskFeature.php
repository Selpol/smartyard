<?php

namespace Selpol\Feature\Task;

use Selpol\Feature\Feature;
use Selpol\Task\Task;

abstract class TaskFeature extends Feature
{
    public abstract function page(int $size, int $page): array;

    public abstract function add(Task $task, string $message, int $status): int;

    public abstract function dispatch(int $id): bool;
}