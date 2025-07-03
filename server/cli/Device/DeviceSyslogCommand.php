<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\Syslog;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\ConfigKey;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;
use Selpol\Service\DeviceService;
use Throwable;

#[Executable('device:syslog', 'Обновить syslog на устройствах')]
class DeviceSyslogCommand
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

            $server = uri($intercom->resolver->string(ConfigKey::CleanSyslog, 'syslog://127.0.0.1:514'));

            if ($intercom instanceof CommonInterface) {
                $syslog = new Syslog($server->getHost(), $server->getPort() !== null && $server->getPort() !== 0 ? $server->getPort() : 514);

                $intercom->setSyslog($syslog);
            }

            $bar->label('Домофонов ' . $count . '/' . $length);
            $bar->advance($step);
        }
    }
}