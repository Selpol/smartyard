<?php

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\DeviceException;
use Selpol\Device\Ip\IpDevice;
use Selpol\Http\Stream;

abstract class CameraDevice extends IpDevice
{
    public function getScreenshot(): Stream
    {
        throw new DeviceException($this);
    }
}