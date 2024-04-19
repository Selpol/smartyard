<?php declare(strict_types=1);

namespace Selpol\Command\Database;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
use Throwable;

#[Executable('database:init', 'Инициализация базы данных')]
class InitCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(?int $version, bool $force): void
    {
        try {
            $coreVar = CoreVar::getRepository()->findByName('database.version');

            $currentVersion = intval($coreVar?->var_value ?? '') ?? 0;
        } catch (Throwable) {
            $currentVersion = 0;
        }

        if ($version !== null) {
            if ($version > $currentVersion) {
                $this->getLogger()->debug('Повышение версии базы данных с ' . $currentVersion . ' к ' . $version);

                if (task(new MigrationUpTask($currentVersion, $version, $force))->sync())
                    $this->getLogger()->debug('Повышение завершено. Новая версия ' . $currentVersion);
            } else if ($version < $currentVersion) {
                $this->getLogger()->debug('Понижение версии базы данных с ' . $currentVersion . ' к ' . $version);

                if (task(new MigrationDownTask($currentVersion, $version, $force))->sync())
                    $this->getLogger()->debug('Понижение завершено. Новая версия ' . $currentVersion);
            }
        } else {
            $this->getLogger()->debug('Повышение версии базы данных с ' . $currentVersion . ' к последней');

            if (task(new MigrationUpTask($currentVersion, null, $force))->sync())
                $this->getLogger()->debug('Повышение завершено. Новая версия ' . $currentVersion);
        }
    }
}