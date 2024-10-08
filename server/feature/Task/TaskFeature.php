<?php declare(strict_types=1);

namespace Selpol\Feature\Task;

use Selpol\Feature\Feature;
use Selpol\Feature\Task\Internal\InternalTaskFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Task\Task;

#[Singleton(InternalTaskFeature::class)]
readonly abstract class TaskFeature extends Feature
{
    public abstract function add(Task $task, string $message, int $status): void;

    public abstract function dispatch(int $id): bool;

    public abstract function getUniques(): array;

    public abstract function setUnique(Task $task): void;

    public abstract function hasUnique(Task $task): bool;

    public abstract function releaseUnique(Task|string $task): void;

    public abstract function clearUnique(): void;
}