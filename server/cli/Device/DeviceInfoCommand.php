<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Entity\Model\Device\DeviceCamera;
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
        $progress = $io->getOutput()->getBar('Device info');

        $progress->show();

        $deviceIntercoms = DeviceIntercom::fetchAll();
        $deviceCameras = DeviceCamera::fetchAll();

        $count = count($deviceIntercoms) + count($deviceCameras);
        $value = 0;
        $step = 1 / $count;

        foreach ($deviceIntercoms as $deviceIntercom) {
            $intercom = $service->intercomByEntity($deviceIntercom);

            if (!$intercom->pingRaw()) {
                continue;
            }

            $info = $intercom->getSysInfo();

            $deviceIntercom->device_id = $info['DeviceID'];
            $deviceIntercom->device_model = $info['DeviceModel'];
            $deviceIntercom->device_software_version = $info['SoftwareVersion'];
            $deviceIntercom->device_hardware_version = $info['HardwareVersion'];

            $deviceIntercom->update();

            $value += $step;
            $progress->set((int)floor($value));
        }

        foreach ($deviceCameras as $deviceCamera) {
            $camera = $service->cameraByEntity($deviceCamera);

            if (!$camera->pingRaw()) {
                continue;
            }

            $info = $camera->getSysInfo();

            $deviceCamera->device_id = $info['DeviceID'];
            $deviceCamera->device_model = $info['DeviceModel'];
            $deviceCamera->device_software_version = $info['SoftwareVersion'];
            $deviceCamera->device_hardware_version = $info['HardwareVersion'];

            $deviceCamera->update();

            $value += $step;
            $progress->set((int)floor($value));
        }

        $progress->hide();

        $io->writeLine('Device info updated');
    }
}