<?php

namespace Selpol\Device\Ip\Camera\Is;

use Selpol\Device\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Http\Stream;
use Throwable;

class IsCamera extends CameraDevice
{
    public string $login = 'root';

    public function getScreenshot(): Stream
    {
        try {
            return $this->client()->get($this->uri . '/camera/snapshot')->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

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