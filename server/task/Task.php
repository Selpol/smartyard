<?php declare(strict_types=1);

namespace Selpol\Task;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

abstract class Task implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public string $title;

    public ?int $uid = null;

    private mixed $progressCallback = null;

    public function __construct(string $title)
    {
        $this->title = $title;

        $segments = explode('\\', static::class);

        $this->setLogger(file_logger('task/' . ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $segments[count($segments) - 1])), '-')));
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
        if ($callback)
            $this->progressCallback = $callback;
        else unset($this->progressCallback);
    }

    protected function setProgress(int|float $progress): void
    {
        if (isset($this->progressCallback) && $this->progressCallback)
            call_user_func($this->progressCallback, $progress);
    }
}