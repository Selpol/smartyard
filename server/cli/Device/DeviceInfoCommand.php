<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Service\DeviceService;
use Throwable;

#[Executable('device:info', 'Обновление информации об устройствах')]
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
        $step = (1 / $count) * 100;

        foreach ($deviceIntercoms as $deviceIntercom) {
            try {
                $intercom = $service->intercomByEntity($deviceIntercom);

                if (!$intercom->pingRaw()) {
                    continue;
                }

                $info = $intercom->getSysInfo();

                $deviceIntercom->device_id = $info->deviceId;
                $deviceIntercom->device_model = $info->deviceModel;
                $deviceIntercom->device_software_version = $info->softwareVersion;
                $deviceIntercom->device_hardware_version = $info->hardwareVersion;

                $deviceIntercom->update();

                $value += $step;
                $progress->set((int)floor($value));
            } catch (KernelException $exception) {
                $io->writeLine($deviceIntercom->house_domophone_id . ' - ' . $deviceIntercom->ip . ' - ' . $exception->getLocalizedMessage());
            } catch (Throwable $throwable) {
                $io->writeLine($deviceIntercom->house_domophone_id . ' - ' . $deviceIntercom->ip . ' - ' . $throwable->getMessage());
            }
        }

        foreach ($deviceCameras as $deviceCamera) {
            try {
                $camera = $service->cameraByEntity($deviceCamera);

                if (!$camera->pingRaw()) {
                    continue;
                }

                $info = $camera->getSysInfo();

                $deviceCamera->device_id = $info->deviceId;
                $deviceCamera->device_model = $info->deviceModel;
                $deviceCamera->device_software_version = $info->softwareVersion;
                $deviceCamera->device_hardware_version = $info->hardwareVersion;

                $deviceCamera->update();

                $value += $step;
                $progress->set((int)floor($value));
            } catch (KernelException $exception) {
                $io->writeLine($deviceCamera->camera_id . ' - ' . $deviceCamera->ip . ' - ' . $exception->getLocalizedMessage());
            } catch (Throwable $throwable) {
                $io->writeLine($deviceCamera->camera_id . ' - ' . $deviceCamera->ip . ' - ' . $throwable->getMessage());
            }
        }

        $progress->hide();

        $io->writeLine('Device info updated');
    }
}