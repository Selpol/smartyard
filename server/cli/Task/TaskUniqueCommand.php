<?php declare(strict_types=1);

namespace Selpol\Cli\Task;

use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('task:unique', 'Очистка списка уникальных задач')]
class TaskUniqueCommand
{
    #[Execute]
    public function execute(CliIO $io, TaskFeature $feature): void
    {
        $feature->clearUnique();
        $io->writeLine('Task unique cleared');
    }
}