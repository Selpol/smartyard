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
        $flats = HouseFlat::fetchAll();

        $intercoms = [];

        $length = count($flats);
        $step = 100.0 / $length;

        $bar = $io->getOutput()->getBar('Обработка 0/' . $length);

        $bar->show();

        for ($i = 0; $i < $length; $i++) {
            $flatEntrances = $flats[$i]->entrances()->fetchAll(setting: setting()->columns(['house_domophone_id']));
            $count = count($flatEntrances);

            if ($count > 0) {
                foreach ($flatEntrances as $flatEntrance) {
                    if (!array_key_exists($flatEntrance->house_domophone_id, $intercoms)) {
                        $intercoms[$flatEntrance->house_domophone_id] = [true, intercom($flatEntrance->house_domophone_id)];
                    } else if (!$intercoms[$flatEntrance->house_domophone_id][0]) {
                        $bar->advance($step / $count);

                        continue;
                    }

                    $intercom = $intercoms[$flatEntrance->house_domophone_id][1];

                    if ($intercom instanceof CodeInterface) {
                        try {
                            if ($flats[$i]->open_code) {
                                $intercom->addCode(new Code(intval($flats[$i]->open_code), intval($flats[$i]->flat)));
                            } else {
                                $intercom->removeCode(new Code(0, $flats[$i]->flat));
                            }
                        } catch (Throwable) {
                            $intercoms[$flatEntrance->house_domophone_id][0] = false;
                        }
                    }

                    $bar->advance($step / $count);
                }
            } else {
                $bar->advance($step);
            }

            $bar->label('Обработка ' . ($i + 1) . '/' . $length);
        }

        $bar->hide();
    }
}
