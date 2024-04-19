<?php declare(strict_types=1);

namespace Selpol\Command\Database;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Feature\Backup\BackupFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('database:backup', 'Сделать бэкап базы данных')]
class BackupCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(string $path): void
    {
        if (file_exists($path))
            $this->getLogger()->debug('Не возможно сделать бэкап в уже существующий файл');

        container(BackupFeature::class)->backup($path);

        $this->getLogger()->debug('Файл бэкапа успешно создан');
    }
}