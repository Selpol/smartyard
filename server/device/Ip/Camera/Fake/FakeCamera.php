<?php

namespace Selpol\Device\Ip\Camera\Fake;

use Selpol\Device\Ip\Camera\CameraDevice;

class FakeCamera extends CameraDevice
{
    public function getSysInfo(): array
    {
        return [
            'DeviceID' => 'FAKE',
            'DeviceModel' => 'FAKE',

            'HardwareVersion' => 'FAKE',
            'SoftwareVersion' => 'FAKE'
        ];
    }

    public function ping(): bool
    {
        return true;
    }
}