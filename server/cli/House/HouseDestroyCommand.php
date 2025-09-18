<?php

declare(strict_types=1);

namespace Selpol\Cli\House;

use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('house:destroy', 'Полностью удалить дом')]
class HouseDestroyCommand
{
    #[Execute]
    public function execute(CliIO $io, HouseFeature $feature): void
    {
        $id = validate('id', $io->readLine('Идентификатор дома> '), rule()->id());

        if ($feature->destroyHouse($id)) {
            $io->writeLine('Дом удален');
        }
    }
}
