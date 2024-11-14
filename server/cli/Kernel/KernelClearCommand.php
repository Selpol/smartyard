<?php declare(strict_types=1);

namespace Selpol\Cli\Kernel;

use Selpol\Framework\Cache\FileCache;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('kernel:clear', 'Удаление файлов оптимизации ядра')]
class KernelClearCommand
{
    #[Execute]
    public function execute(CliIO $io, FileCache $cache): void
    {
        $cache->clear();
        $io->writeLine('Kernel cleared');
    }
}