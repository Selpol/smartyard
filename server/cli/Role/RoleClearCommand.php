<?php declare(strict_types=1);

namespace Selpol\Cli\Role;

use Selpol\Entity\Model\Permission;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('role:clear', 'Удаление ролей и прав')]
class RoleClearCommand
{
    #[Execute]
    public function execute(): void
    {
        Permission::getRepository()->deleteSql();
    }
}