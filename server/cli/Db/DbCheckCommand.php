<?php declare(strict_types=1);

namespace Selpol\Cli\Db;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Service\DatabaseService;
use Throwable;

#[Executable('db:check', 'Проверка базы данных')]
class DbCheckCommand
{
    #[Execute]
    public function execute(DatabaseService $service): int
    {
        try {
            $result = $service->get('SELECT 1 as result', options: ['singlify']);

            $lastError = last_error();

            if ($lastError !== null) {
                echo $lastError;

                return 1;
            }

            $result = $result['result'] == 1 ? 0 : 1;

            echo $result . PHP_EOL;

            return $result;
        } catch (Throwable $throwable) {
            echo $throwable->getMessage();

            return 1;
        }
    }
}