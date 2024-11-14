<?php declare(strict_types=1);

namespace Selpol\Cli\Db;

use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
use Throwable;

#[Executable('db:init', 'Настройка базы данных')]
class DbInitCommand
{
    #[Execute]
    public function execute(CliIO $io, ?int $version, bool $force): void
    {
        try {
            $coreVar = CoreVar::getRepository()->findByName('database.version');

            $coreVersion = intval($coreVar?->var_value ?? '') ?? 0;
        } catch (Throwable) {
            $coreVersion = 0;
        }

        if ($version !== null) {
            if ($version > $coreVersion) {
                $io->writeLine('Start upgrading migration from ' . $coreVersion . ' to ' . $version);

                if (task(new MigrationUpTask($coreVersion, $version, $force))->sync()) {
                    $io->writeLine('Upgrade migration from ' . $coreVersion . ' to ' . $version);
                }
            } elseif ($version < $coreVersion) {
                $io->writeLine('Start downgrading migration from ' . $coreVersion . ' to ' . $version);

                if (task(new MigrationDownTask($coreVersion, $version, $force))->sync()) {
                    $io->writeLine('Downgrade migration from ' . $coreVersion . ' to ' . $version);
                }
            }
        } else {
            $io->writeLine('Start upgrading migration from ' . $coreVersion . ' to latest');

            if (task(new MigrationUpTask($coreVersion, null, $force))->sync()) {
                $io->writeLine('Upgrade migration from ' . $coreVersion . ' to latest');
            }
        }
    }
}