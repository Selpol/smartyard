<?php

namespace Selpol\Feature\Task\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Entity\Repository\TaskRepository;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Task\Task;
use Selpol\Validator\Exception\ValidatorException;

class InternalTaskFeature extends TaskFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function page(int $size, int $page): array
    {
        return container(TaskRepository::class)->fetchAllRaw('SELECT id, title, message, status, created_at, updated_at FROM task ORDER BY created_at DESC OFFSET :page LIMIT :size', ['page' => $page * $size, 'size' => $size]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function add(Task $task, string $message, int $status): void
    {
        $dbTask = new \Selpol\Entity\Model\Task();

        $dbTask->data = serialize($task);
        $dbTask->title = $task->title;
        $dbTask->message = $message;
        $dbTask->status = $status;

        container(TaskRepository::class)->insert($dbTask);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function dispatch(int $id): bool
    {
        $dbTask = container(TaskRepository::class)->findById($id);

        $task = unserialize($dbTask->data);

        if ($task instanceof Task)
            return task($task)->high()->dispatch();

        logger('frontend')->error('Unknown type', [$task, 'data' => $dbTask->data]);

        return false;
    }
}