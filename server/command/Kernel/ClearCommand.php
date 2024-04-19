<?php declare(strict_types=1);

namespace Selpol\Command\Kernel;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cache\FileCache;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('kernel:clear', 'Удалить файлы оптимизации')]
class ClearCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(FileCache $cache): void
    {
        $cache->clear();

        $this->getLogger()->debug('Файлы оптимизации удалены');
    }
}