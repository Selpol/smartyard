<?php

namespace Selpol\Feature\Task\Internal;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Entity\Repository\TaskRepository;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Task\Task;
use Selpol\Task\TaskUniqueInterface;
use Selpol\Validator\Exception\ValidatorException;

readonly class InternalTaskFeature extends TaskFeature
{
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

        file_logger('frontend')->error('Unknown type', [$task, 'data' => $dbTask->data]);

        return false;
    }

    /**
     * @throws RedisException
     */
    public function setUnique(Task $task): void
    {
        if ($task instanceof TaskUniqueInterface) {
            $unique = $task->unique();

            $this->getRedis()->getConnection()->setex('task:unique:' . $unique[0], $unique[1], $unique[0]);
        }
    }

    /**
     * @throws RedisException
     */
    public function hasUnique(Task $task): bool
    {
        if ($task instanceof TaskUniqueInterface) {
            $unique = $task->unique();

            return $this->getRedis()->getConnection()->exists('task:unique:' . $unique[0]) === 1;
        }

        return false;
    }

    /**
     * @throws RedisException
     */
    public function releaseUnique(Task $task): void
    {
        if ($task instanceof TaskUniqueInterface) {
            $unique = $task->unique();

            $this->getRedis()->getConnection()->del('task:unique:' . $unique[0]);
        }
    }

    /**
     * @throws RedisException
     */
    public function clearUnique(): void
    {
        $keys = $this->getRedis()->getConnection()->keys('task:unique:*');

        if (is_array($keys))
            $this->getRedis()->getConnection()->del(...$keys);
    }
}