<?php declare(strict_types=1);

namespace Selpol\Runner;

use Exception;
use Psr\Log\LoggerInterface;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Service\TaskService;
use Selpol\Task\Task;
use Selpol\Task\TaskCallback;
use Throwable;

class TaskRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait;

    /**
     * @throws Exception
     */
    function run(array $arguments): int
    {
        $arguments = $this->getArguments($arguments);

        $queue = array_key_exists('--queue', $arguments) ? $arguments['--queue'] : 'default';

        $this->registerSignal();
        $this->registerDequeue($queue);

        return 0;
    }

    public function error(Throwable $throwable): int
    {
        $this->logger->error($throwable);

        return 0;
    }

    private function getArguments(array $arguments): array
    {
        $args = [];

        for ($i = 1; $i < count($arguments); $i++) {
            $a = explode('=', $arguments[$i]);

            $args[$a[0]] = @$a[1];
        }

        return $args;
    }

    private function registerSignal(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            sapi_windows_set_ctrl_handler(static function (int $event) {
                if ($event == PHP_WINDOWS_EVENT_CTRL_C)
                    exit(0);
            });
        else {
            pcntl_async_signals(true);

            pcntl_signal(SIGINT, static fn() => exit(0));
            pcntl_signal(SIGTERM, static fn() => exit(0));
        }
    }

    /**
     * @throws Exception
     */
    private function registerDequeue(string $queue): void
    {
        $service = container(TaskService::class);
        $service->setLogger(file_logger('task'));

        $service->dequeue($queue, new class($queue, file_logger('task-' . $queue)) implements TaskCallback {
            private string $queue;

            private LoggerInterface $logger;

            public function __construct(string $queue, LoggerInterface $logger)
            {
                $this->queue = $queue;

                $this->logger = $logger;
            }

            public function __invoke(Task $task): void
            {
                $this->logger->info('Dequeue start task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);

                try {
                    $task->onTask();

                    container(TaskFeature::class)->add($task, 'OK', 1);

                    $this->logger->info('Dequeue complete task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);
                } catch (Throwable $throwable) {
                    $this->logger->info('Dequeue error task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title, 'message' => $throwable->getMessage()]);

                    $task->onError($throwable);

                    container(TaskFeature::class)->add($task, $throwable->getMessage(), 0);
                }
            }
        });
    }
}