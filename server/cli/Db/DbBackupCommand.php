<?php declare(strict_types=1);

namespace Selpol\Cli\Db;

use Selpol\Feature\Backup\BackupFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('db:backup', 'Бэкап базы данных')]
class DbBackupCommand
{
    #[Execute]
    public function execute(CliIO $io, BackupFeature $feature, string $path): int
    {
        if (file_exists($path)) {
            $io->writeLine('Не возможно сделать бэкап в уже существующий файл');

            return 1;
        }

        return $feature->backup($path) ? 0 : 1;
    }
}