<?php declare(strict_types=1);

namespace Selpol\Command\Database;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Feature\Backup\BackupFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('database:restore', 'Восстановление базы данных')]
class RestoreCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(string $path): void
    {
        if (file_exists($path))
            $this->getLogger()->debug('Файла бэкапа не существует');

        container(BackupFeature::class)->restore($path);
        $this->getLogger()->debug('Файл бэкапа успешно восстановлен');
    }
}