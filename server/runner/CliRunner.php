<?php

namespace Selpol\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Framework\Cli\Trait\CliTrait;
use Selpol\Framework\Cli\Trait\HandlerTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Throwable;

class CliRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerRunnerTrait;

    use CliTrait;
    use HandlerTrait;

    public function __construct()
    {
        $this->setLogger(file_logger('cli'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function run(array $arguments): int
    {
        chdir(path(''));

        $arguments = $this->getArguments($arguments);
        $command = $this->getCli()->match($arguments);

        if ($command == null) {
            echo $this->getCli()->help() . PHP_EOL;

            return 0;
        }

        $this->logger?->debug('Handle command', ['title' => $command->title, 'class' => $command->class]);

        $result = $this->handle($command);

        if (gettype($result) != "NULL") {
            $this->logger?->debug('Handle command complete', ['title' => $command->title, 'class' => $command->class, 'result' => $result]);
        }

        return 0;
    }

    public function error(Throwable $throwable): int
    {
        echo $throwable;

        return 0;
    }

    private function getArguments(array $arguments): array
    {
        $args = [];
        $counter = count($arguments);

        for ($i = 1; $i < $counter; ++$i) {
            $a = explode('=', (string)$arguments[$i]);

            $args[$a[0]] = @$a[1];
        }

        return $args;
    }
}