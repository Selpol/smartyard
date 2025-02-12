<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\Asterisk\Contact;
use Selpol\Service\AsteriskService;
use Selpol\Service\DeviceService;

#[Executable('device:health', 'Восстановление устройств')]
class DeviceHealthCommand
{
    #[Execute]
    public function execute(CliIO $io, DeviceService $deviceService, AsteriskService $asteriskService): void
    {
        $io->getOutputCursor()->erase();

        $bar = $io->getOutput()->getBar('Домофоны');

        $bar->show();

        $deviceIntercoms = DeviceIntercom::fetchAll();

        $devices = array_filter(
            array_map(fn(DeviceIntercom $intercom) => $deviceService->intercomByEntity($intercom), $deviceIntercoms),
            static fn(IntercomDevice $device) => $device->pingRaw()
        );

        $percent = 0;
        $step = 100.0 / count($devices) / 3.0;

        $contacts = $asteriskService->contacts();

        foreach ($devices as $device) {
            if (count($contacts) > 0) {
                if ($device instanceof SipInterface) {
                    $this->health($device, $contacts, 0);
                }
            }

            $percent += $step;
            $bar->set((int) ceil($percent));
        }

        $contacts = $asteriskService->contacts();

        if (count($contacts) > 0) {
            foreach ($devices as $device) {
                if ($device instanceof SipInterface) {
                    $this->health($device, $contacts, 1);
                }

                $percent += $step;
                $bar->set((int) ceil($percent));
            }

            sleep(30);

            $contacts = $asteriskService->contacts();

            if (count($contacts) > 0) {
                foreach ($devices as $device) {
                    if ($device instanceof SipInterface) {
                        $this->health($device, $contacts, state: 2);
                    }

                    $percent += $step;
                    $bar->set((int) ceil($percent));
                }
            }
        }

        $bar->hide();
    }

    /**
     * @param IntercomDevice|SipInterface $device
     * @param Contact[] $contacts
     * @param int $state 
     * @return void
     */
    public function health(IntercomDevice|SipInterface $device, array $contacts, int $state): void
    {
        if (!$device->model->isBeward()) {
            return;
        }

        if (!$device->intercom->ip) {
            return;
        }

        for ($i = 0; $i < count($contacts); $i++) {
            if ($contacts[$i]->ip == $device->intercom->ip) {
                return;
            }
        }

        if ($state == 0) {
            $device->get('/webs/SIP1CfgEx', ['cksip' => 0, 'ckenablesip' => 0]);

            sleep(1);

            $device->get('/webs/SIP1CfgEx', ['cksip' => 1, 'ckenablesip' => 1]);

            echo 'SIP: ' . $device->intercom->ip . PHP_EOL;
        } else if ($state == 1) {
            $device->reboot();

            echo 'REBOOT: ' . $device->intercom->ip . PHP_EOL;
        } else if ($state == 2) {
            echo 'STATE: ' . $device->intercom->ip . PHP_EOL;
        }
    }
}