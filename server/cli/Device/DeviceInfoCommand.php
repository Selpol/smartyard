<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DeviceService;

#[Executable('device:info', 'Очиста данных аудита')]
class DeviceInfoCommand
{
    #[Execute]
    public function execute(CliIO $io, DeviceService $service): void
    {
        $deviceIntercoms = DeviceIntercom::fetchAll();

        foreach ($deviceIntercoms as $deviceIntercom) {
            $intercom = $service->intercomByEntity($deviceIntercom);

            if (!$intercom->ping()) {
                continue;
            }

            $info = $intercom->getSysInfo();

            $deviceIntercom->device_id = $info['DeviceID'];
            $deviceIntercom->device_model = $info['DeviceModel'];
            $deviceIntercom->device_software_version = $info['SoftwareVersion'];
            $deviceIntercom->device_hardware_version = $info['HardwareVersion'];

            $deviceIntercom->update();
        }

        $io->writeLine('Device info updated');
    }
}