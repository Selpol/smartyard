<?php declare(strict_types=1);

namespace Selpol\Cli\Amqp;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('amqp:check', 'Проверка состояния AMQP')]
class AmqpCheckCommand
{
    #[Execute]
    public function execute(): int
    {
        return 0;
    }
}