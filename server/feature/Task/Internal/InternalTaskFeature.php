<?php declare(strict_types=1);

namespace Selpol\Feature\Task\Internal;

use Psr\Container\NotFoundExceptionInterface;
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

        $dbTask->insert();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function dispatch(int $id): bool
    {
        $dbTask = \Selpol\Entity\Model\Task::findById($id, setting: setting()->nonNullable());

        $task = unserialize($dbTask->data);

        if ($task instanceof Task)
            return task($task)->high()->dispatch();

        file_logger('frontend')->error('Unknown type', [$task, 'data' => $dbTask->data]);

        return false;
    }

    public function setUnique(Task $task): void
    {
        if ($task instanceof TaskUniqueInterface) {
            $unique = $task->unique();

            $this->getRedis()->setEx('task:unique:' . $unique[0], $unique[1], $unique[0]);
        }
    }

    public function hasUnique(Task $task): bool
    {
        if ($task instanceof TaskUniqueInterface) {
            $unique = $task->unique();

            return $this->getRedis()->exist('task:unique:' . $unique[0]);
        }

        return false;
    }

    public function releaseUnique(Task $task): void
    {
        if ($task instanceof TaskUniqueInterface) {
            $unique = $task->unique();

            $this->getRedis()->del('task:unique:' . $unique[0]);
        }
    }

    public function clearUnique(): void
    {
        $keys = $this->getRedis()->keys('task:unique:*');

        if (count($keys))
            $this->getRedis()->del(...$keys);
    }
}