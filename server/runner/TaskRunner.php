<?php declare(strict_types=1);

namespace Selpol\Runner;

use Exception;
use Psr\Log\LoggerInterface;
use Selpol\Feature\Mqtt\MqttFeature;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Service\MqttService;
use Selpol\Service\TaskService;
use Selpol\Task\Task;
use Selpol\Task\TaskCallbackInterface;
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

        $logger = file_logger('task-' . $queue);

        $service->dequeue($queue, static function (Task $task) use ($queue, $logger) {
            $logger->info('Dequeue start task', ['queue' => $queue, 'class' => get_class($task), 'title' => $task->title]);

            $feature = container(TaskFeature::class);
            $service = container(MqttService::class);

            $uuid = guid_v4();

            try {
                $service->task($uuid, $task->title, 'start', 0);

                $task->setProgressCallback(static fn(int|float $progress) => $service->task($uuid, $task->title, 'progress', $progress));

                $task->onTask();

                $task->setProgressCallback(null);

                $feature->add($task, 'OK', 1);

                $logger->info('Dequeue complete task', ['queue' => $queue, 'class' => get_class($task), 'title' => $task->title]);
            } catch (Throwable $throwable) {
                $logger->info('Dequeue error task', ['queue' => $queue, 'class' => get_class($task), 'title' => $task->title, 'message' => $throwable->getMessage()]);

                $feature->add($task, $throwable->getMessage(), 0);

                $task->onError($throwable);
            } finally {
                $feature->releaseUnique($task);

                $service->task($uuid, $task->title, 'done', 100);
            }
        });
    }
}