<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Is;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Trait\IsTrait;
use Selpol\Http\Stream;
use Throwable;

class IsCamera extends CameraDevice
{
    use IsTrait;

    public function getScreenshot(): Stream
    {
        try {
            return $this->client()->get($this->uri . '/camera/snapshot', ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)])->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}