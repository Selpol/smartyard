<?php declare(strict_types=1);

namespace Selpol\Cli\Db;

use Selpol\Feature\Backup\BackupFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('db:restore', 'Восстановление базы данных')]
class DbRestoreCommand
{
    #[Execute]
    public function execute(CliIO $io, BackupFeature $feature, string $path): int
    {
        if (!file_exists($path)) {
            $io->writeLine('Файла бэкапа не существует');

            return 1;
        }

        return $feature->restore($path) ? 0 : 1;
    }
}