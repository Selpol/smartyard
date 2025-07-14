<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DeviceService;
use Throwable;

#[Executable('device:video', 'Обновить видео на устройствах')]
class DeviceVideoCommand
{
    #[Execute]
    public function execute(CliIO $io, DeviceService $service): void
    {
        $devices = DeviceIntercom::fetchAll();

        $length = count($devices);
        $step = 100 / $length;
        $count = 0;

        $bar = $io->getOutput()->getBar('Домофонов 0/' . $length);

        foreach ($devices as $device) {
            $count++;

            if (!$device->model) {
                $bar->label('Домофонов ' . $count . '/' . $length);
                $bar->advance($step);

                continue;
            }

            $intercom = $service->intercomByEntity($device);

            if (!$intercom) {
                $bar->label('Домофонов ' . $count . '/' . $length);
                $bar->advance($step);

                continue;
            }

            try {
                if (!$intercom->getSysInfo()->deviceId) {
                    $bar->label('Домофонов ' . $count . '/' . $length);
                    $bar->advance($step);

                    continue;
                }
            } catch (Throwable) {
                $bar->label('Домофонов ' . $count . '/' . $length);
                $bar->advance($step);
            
                continue;
            }

            if ($intercom instanceof VideoInterface) {
                $videoEncoding = $intercom->getVideoEncoding();

                $videoEncoding->quality = $intercom->resolver->string(ConfigKey::VideoQuality);
                $videoEncoding->primaryBitrate = $intercom->resolver->int(ConfigKey::VideoPrimaryBitrate, 1024);
                $videoEncoding->secondaryBitrate = $intercom->resolver->int(ConfigKey::VideoSecondaryBitrate, 512);

                $intercom->setVideoEncoding($videoEncoding);
            }

            $bar->label('Домофонов ' . $count . '/' . $length);
            $bar->advance($step);
        }
    }
}