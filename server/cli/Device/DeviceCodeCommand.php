<?php

declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\Setting\Code\Code;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Throwable;

#[Executable('device:code', 'Обновление кодов на всех устройствах')]
class DeviceCodeCommand
{
    #[Execute]
    public function execute(CliIO $io): void
    {
        /** @var HouseFlat[] $flats */
        $flats = HouseFlat::fetchAll(criteria()->asc('house_flat_id'));

        /** @var array<int, array<int, int>> $intercoms */
        $intercoms = [];

        $length = count($flats);
        $step = 100.0 / $length;

        $bar = $io->getOutput()->getBar('Поиск 0/' . $length);

        $bar->show();

        $i = 1;

        foreach ($flats as $flat) {
            $entrances = $flat->entrances()->fetchAll(setting: setting()->columns(['house_domophone_id']));

            foreach ($entrances as $entrance) {
                if (!array_key_exists($entrance->house_domophone_id, $intercoms)) {
                    $intercoms[$entrance->house_domophone_id] = [];
                }

                $intercoms[$entrance->house_domophone_id][intval($flat->flat)] = intval($flat->open_code);
            }

            $bar->label('Поиск ' . ($i++) . '/' . $length);
            $bar->advance($step);
        }

        $bar->hide();

        $length = count($intercoms);
        $step = 100.0 / $length;

        $bar = $io->getOutput()->getBar('Обработка 0/' . $length);

        $bar->show();

        $i = 1;

        foreach ($intercoms as $id => $flats) {
            $intercom = intercom($id);

            try {
                if ($intercom instanceof CodeInterface) {
                    foreach ($flats as $flat => $code) {
                        if ($code != 0) {
                            $intercom->addCode(new Code($code, $flat));
                        } else {
                            $intercom->removeCode(new Code(0, $flat));
                        }
                    }
                }
            } catch (Throwable $throwable) {
                $io->writeLine('Домофон ' . $id . ' ' . $throwable->getMessage());
            }

            $bar->label('Обработка ' . ($i++) . '/' . $length);
            $bar->advance($step);
        }

        $bar->hide();
    }
}
