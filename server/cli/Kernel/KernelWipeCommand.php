<?php declare(strict_types=1);

namespace Selpol\Cli\Kernel;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\PrometheusService;

#[Executable('kernel:wipe', 'Удаление метриков ядра ядра')]
class KernelWipeCommand
{
    #[Execute]
    public function execute(CliIO $io, PrometheusService $service): void
    {
        $service->wipe();
        $io->writeLine('Kernel wiped');
    }
}