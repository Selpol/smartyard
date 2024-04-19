<?php declare(strict_types=1);

namespace Selpol\Command\Role;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('role:clear', 'Удалить права')]
class ClearCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(): void
    {
        Permission::getRepository()->deleteSql();
        $this->getLogger()->debug('Права удалены');
    }
}