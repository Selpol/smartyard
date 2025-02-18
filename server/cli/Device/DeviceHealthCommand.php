<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DeviceService;

#[Executable('device:health', 'Восстановление устройств, без регистрации')]
class DeviceHealthCommand
{
    #[Execute]
    public function execute(CliIO $io, DeviceService $deviceService, SipFeature $sipFeature): void
    {
        $io->getOutputCursor()->erase();

        $devices = array_map(fn(DeviceIntercom $intercom) => $deviceService->intercomByEntity($intercom), DeviceIntercom::fetchAll());
        $length = count($devices);
        $step = 100.0 / $length;

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

            if ($status) {
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
                'MODEL' => $devices[$i]->model->vendor,
                'ENTRANCE' => $entrances[0]->house_entrance_id,
                'LINK' => $web . '/houses/' . $houses[0]->address_house_id . '?houseTab=entrances'
            ];

            $server = $sipFeature->server('ip', $devices[$i]->intercom->server)[0];

            $device->setSip(new Sip(
                sprintf("1%05d", $devices[$i]->intercom->house_domophone_id),
                $devices[$i]->password,
                $server->internal_ip,
                $server->internal_port
            ));

            $bar->label('Обработка ' . ($i + 1) . '/' . $length);
            $bar->advance($step);
        }

        $bar->hide();

        $io->getOutputCursor()->erase();
        $io->getOutput()->table(['IP', 'MODEL', 'ENTRANCE', 'LINK'], $result);

        $io->writeLine(count($result) . '/' . $count);
    }
}