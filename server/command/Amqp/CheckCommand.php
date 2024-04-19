<?php declare(strict_types=1);

namespace Selpol\Command\Amqp;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('amqp:check', 'Проверка Amqp')]
class CheckCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(): void
    {
        echo 1 . PHP_EOL;
    }
}