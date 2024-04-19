<?php declare(strict_types=1);

namespace Selpol\Command\Database;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Service\DatabaseService;

#[Executable('database:check', 'Проверка работоспособности базы данных')]
class CheckCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(DatabaseService $service): void
    {
        $result = $service->get('SELECT 1 as result', options: ['singlify']);

        $lastError = last_error();

        if ($lastError !== null) {
            echo $lastError . PHP_EOL;

            return;
        }

        $result = $result['result'] == 1 ? 0 : 1;

        echo $result . PHP_EOL;
    }
}