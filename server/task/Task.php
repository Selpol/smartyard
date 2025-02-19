<?php declare(strict_types=1);

namespace Selpol\Task;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

abstract class Task implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public ?int $uid = null;
    public bool $progress = false;

    private mixed $progressCallback = null;

    public function __construct(public string $title)
    {
        $this->progress = config_get('mqtt.progress', false);

        $this->setLogger(file_logger('task'));
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public abstract function onTask(): mixed;

    public function onError(Throwable $throwable): void
    {
    }

    public function setProgressCallback(?callable $callback): void
    {
        if ($callback !== null) {
            $this->progressCallback = $callback;
        } else {
            unset($this->progressCallback);
        }
    }

    protected function setProgress(int|float $progress): void
    {
        if (is_callable($this->progressCallback)) {
            call_user_func($this->progressCallback, $progress);
        }
    }
}