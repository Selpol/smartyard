<?php declare(strict_types=1);

namespace Selpol\Kernel;

interface KernelShutdownCallback
{
    public function __invoke(Kernel $kernel): void;
}