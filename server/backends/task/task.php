<?php

namespace backends\task;

use backends\backend;

abstract class task extends backend
{
    public abstract function page(int $size, int $page): array;

    public abstract function add(\Selpol\Task\Task $task, string $message, int $status): int;

    public abstract function update(int $id, string $message, int $status): bool;

    public abstract function dispatch(int $id): bool;
}