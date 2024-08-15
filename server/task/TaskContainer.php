<?php declare(strict_types=1);

namespace Selpol\Task;

use Exception;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Service\AuthService;
use Selpol\Service\TaskService;
use Throwable;

class TaskContainer
{
    private ?string $queue = null;

    private ?int $start = null;

    public function __construct(private readonly Task $task)
    {
    }

    public function queue(?string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function high(): static
    {
        return $this->queue(TaskService::QUEUE_HIGH);
    }

    public function default(): static
    {
        return $this->queue(TaskService::QUEUE_DEFAULT);
    }

    public function low(): static
    {
        return $this->queue(TaskService::QUEUE_LOW);
    }

    public function delay(?int $start): static
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function sync(): mixed
    {
        return $this->task->onTask();
    }

    public function dispatch(): bool
    {
        $logger = file_logger('task');

        $queue = $this->queue ?? TaskService::QUEUE_DEFAULT;

        try {
            $canAudit = container(AuditFeature::class)->canAudit();

            if ($canAudit && $this->task->uid === null) {
                $this->task->uid = container(AuthService::class)->getUserOrThrow()->getIdentifier();
            }

            container(TaskService::class)->enqueue($queue, $this->task, $this->start);

            if ($canAudit) {
                container(AuditFeature::class)->audit('-1', $this->task::class, 'task', $this->task->title);
            }

            return true;
        } catch (Throwable $throwable) {
            $logger->error('Error dispatching task' . PHP_EOL . $throwable);

            if ($throwable instanceof KernelException) {
                throw $throwable;
            }

            return false;
        }
    }
}