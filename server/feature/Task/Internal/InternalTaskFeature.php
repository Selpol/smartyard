<?php

namespace Selpol\Feature\Task\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Task\Task;

class InternalTaskFeature extends TaskFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function page(int $size, int $page): array
    {
        return $this->getDatabase()->get('SELECT id, title, message, status, created_at, updated_at FROM task ORDER BY created_at DESC OFFSET :page LIMIT :size', ['page' => $page * $size, 'size' => $size]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function add(Task $task, string $message, int $status): int
    {
        $db = $this->getDatabase();

        $id = $db->get("SELECT NEXTVAL('task_id_seq')", options: ['singlify'])['nextval'];

        $statement = $db->prepare('INSERT INTO task(id, data, title, message, status) VALUES (:id, :data, :title, :message, :status)');

        return $statement->execute(['id' => $id, 'data' => serialize($task), 'title' => $task->title, 'message' => $message, 'status' => $status]) ? $id : -1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function dispatch(int $id): bool
    {
        $dbTask = $this->getDatabase()->get('SELECT data FROM task WHERE id = :id', ['id' => $id], options: ['singlify']);

        if (!$dbTask) {
            last_error('Задача не найдена');

            return false;
        }

        $task = unserialize($dbTask['data']);

        if ($task instanceof Task)
            return task($task)->high()->dispatch();

        logger('frontend')->error('Unknown type', [$task, 'data' => $dbTask['data']]);

        return false;
    }
}