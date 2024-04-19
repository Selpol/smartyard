<?php

namespace Selpol\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Framework\Cli\Trait\CliTrait;
use Selpol\Framework\Cli\Trait\HandlerTrait;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Throwable;

class CliRunner implements RunnerInterface, RunnerExceptionHandlerInterface
{
    use LoggerKernelTrait, CliTrait, HandlerTrait;

    public function __construct()
    {
        $this->setLogger(stack_logger([file_logger('cli'), echo_logger()]));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    function run(array $arguments): int
    {
        chdir(path(''));

        $this->loadCli();

        $arguments = $this->getCli()->parse($arguments);
        $command = $this->getCli()->match($arguments);

        if ($command) {
            if (array_key_exists('help', $arguments))
                echo $this->getCli()->help($command->title, true) . PHP_EOL;
            else $this->handle($command);
        } else echo $this->getCli()->help(count($arguments) > 0 ? array_key_first($arguments) : null) . PHP_EOL;

        return 0;
    }

    public function error(Throwable $throwable): int
    {
        $this->logger->error($throwable);

        return 0;
    }

    /**
     * @param string[] $headers
     * @param array $values
     * @return string
     */
    public static function table(array $headers, array $values): string
    {
        $mask = array_reduce($headers, static function (string $previous, string $header) use ($values) {
                $max = strlen($header);

                foreach ($values as $value) {
                    if (strlen($value[$header]) > $max)
                        $max = strlen($value[$header]);
                }

                return $previous . ' | %' . $max . '.' . $max . 's';
            }, '') . ' | ';

        $result = sprintf($mask, ...$headers);
        $result .= PHP_EOL . str_repeat('-', strlen($result)) . PHP_EOL;

        foreach ($values as $value)
            $result .= sprintf($mask, ...array_map(static fn(string $header) => $value[$header], $headers)) . PHP_EOL;

        return $result;
    }
}