<?php

declare(strict_types=1);

namespace Selpol\Cli\House;

use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Task\Tasks\QrTask;

#[Executable('house:qr', 'Создать QR архив')]
class HouseQrCommand
{
    #[Execute]
    public function execute(CliIO $io): void
    {
        $id = validate('id', $io->readLine('Идентификатор дома> '), rule()->id());

        $result = task(new QrTask($id, true))->sync();

        $io->writeLine($result);
    }
}
