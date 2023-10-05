<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

use Selpol\Device\Exception\DeviceException;
use Throwable;

trait IsTrait
{
    public function getSysInfo(): array
    {
        try {
            $info = $this->get('/system/info');
            $version = $this->get('/v2/system/versions');

            return [
                'DeviceID' => $info['chipId'],
                'DeviceModel' => $info['model'],

                'HardwareVersion' => $version['opt']['versions']['hw']['name'],
                'SoftwareVersion' => $version['opt']['name']
            ];
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}