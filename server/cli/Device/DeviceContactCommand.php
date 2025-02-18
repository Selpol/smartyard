<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\Asterisk\Contact;
use Selpol\Service\AsteriskService;
use Selpol\Service\DeviceService;

#[Executable('device:contact', 'Список устройств, без контактов')]
class DeviceContactCommand
{
    #[Execute]
    public function execute(CliIO $io, DeviceService $deviceService, AsteriskService $asteriskService): void
    {
        $io->getOutputCursor()->erase();

        $devices = array_map(fn(DeviceIntercom $intercom) => $deviceService->intercomByEntity($intercom), DeviceIntercom::fetchAll());
        $length = count($devices);
        $step = 100.0 / $length;

        $contacts = array_reduce(
            $asteriskService->contacts(),
            static function (array $previous, Contact $current): array {
                $previous[$current->ip] = true;

                return $previous;
            },
            []
        );

        $count = 0;
        $result = [];

        $bar = $io->getOutput()->getBar('Обработка 0/' . $length);

        $bar->show();

        $web = config_get('api.web', 'http://127.0.0.1');

        for ($i = 0; $i < $length; $i++) {
            $device = $devices[$i];

            if ($device == null || $device->model == null || !$device->pingRaw()) {
                $bar->label('Обработка ' . ($i + 1) . '/' . $length);
                $bar->advance($step);

                continue;
            }

            $count++;

            if (!($device instanceof SipInterface)) {
                $bar->label('Обработка ' . ($i + 1) . '/' . $length);
                $bar->advance($step);

                continue;
            }

            $status = $device->getSipStatus();

            if (!$status || array_key_exists($devices[$i]->intercom->ip, $contacts)) {
                $bar->label('Обработка ' . ($i + 1) . '/' . $length);
                $bar->advance($step);

                continue;
            }

            $entrances = $devices[$i]->intercom->entrances()->fetchAll(criteria()->limit(1));

            if (count($entrances) == 0) {
                $bar->label('Обработка ' . ($i + 1) . '/' . $length);
                $bar->advance($step);

                continue;
            }

            $houses = $entrances[0]->houses()->fetchAll(criteria()->limit(1));

            if (count($houses) == 0) {
                $bar->label('Обработка ' . ($i + 1) . '/' . $length);
                $bar->advance($step);

                continue;
            }

            $result[] = [
                'IP' => $devices[$i]->intercom->ip,
                'TYPE' => $entrances[0]->entrance_type,
                'MODEL' => $devices[$i]->model->vendor,
                'LINK' => $web . '/houses/' . $houses[0]->address_house_id . '?houseTab=entrances&entranceId=' . $entrances[0]->house_entrance_id
            ];

            $bar->label('Обработка ' . ($i + 1) . '/' . $length);
            $bar->advance($step);
        }

        $bar->hide();

        $io->getOutputCursor()->erase();
        $io->getOutput()->table(['IP', 'TYPE', 'MODEL', 'LINK'], $result);

        $io->writeLine(count($result) . '/' . $count);
    }
}