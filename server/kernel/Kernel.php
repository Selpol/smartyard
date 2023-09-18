<?php

namespace Selpol\Kernel;

use Selpol\Kernel\Trait\ConfigTrait;
use Selpol\Kernel\Trait\ContainerTrait;
use Selpol\Kernel\Trait\EnvTrait;
use Throwable;

class Kernel
{
    use EnvTrait;
    use ConfigTrait;
    use ContainerTrait;

    private static ?Kernel $instance = null;

    private KernelRunner $runner;

    /** @var KernelShutdownCallback[] $shutdownCallbacks */
    private array $shutdownCallbacks = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public function setRunner(KernelRunner $runner): static
    {
        $this->runner = $runner;

        return $this;
    }

    public function addShutdownCallback(KernelShutdownCallback|callable $callback): static
    {
        $this->shutdownCallbacks[] = $callback;

        return $this;
    }

    public function removeShutdownCallback(KernelShutdownCallback|callable $callback): static
    {
        $this->shutdownCallbacks[] = $callback;

        return $this;
    }

    public function bootstrap(): static
    {
        mb_internal_encoding('UTF-8');
        date_default_timezone_set('Europe/Moscow');

        $this->loadEnv();
        $this->loadConfig();
        $this->loadContainer();

        require_once path('backends/backend.php');

        register_shutdown_function([$this, 'shutdown']);
        //set_error_handler([$this, 'error']);

        return $this;
    }

    public function run(): int
    {
        try {
            return $this->runner->__invoke($this);
        } catch (Throwable $throwable) {
            if (isset($this->runner))
                return $this->runner->onFailed($throwable, false);

            return 1;
        }
    }

    private function shutdown(): void
    {
        foreach ($this->shutdownCallbacks as $callback)
            $callback($this);

        if (isset($this->container))
            $this->container->dispose();
    }

//    public function error(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null, ?array $errcontext = null): void
//    {
//        logger('kernel')->emergency('Kernel error', ['errno' => $errno, 'errstr' => $errstr, 'errfile' => $errline, 'errline' => $errfile, 'errcontext' => $errcontext]);
//
//        exit($this->runner->onFailed(new RuntimeException(), true));
//    }

    public static function instance(): ?Kernel
    {
        return self::$instance;
    }
}