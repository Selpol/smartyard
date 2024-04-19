<?php declare(strict_types=1);

namespace Selpol\Command\Task;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('task:clear', 'Удалить уникальные задачи')]
class ClearCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(TaskFeature $feature): void
    {
        $feature->clearUnique();
        $this->getLogger()->debug('Уникальные задачи удалены');
    }
}