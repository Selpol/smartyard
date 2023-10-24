<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Is;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Trait\IsTrait;
use Selpol\Framework\Http\Stream;
use Throwable;

class IsCamera extends CameraDevice
{
    use IsTrait;

    public function getScreenshot(): Stream
    {
        try {
            return $this->client->send(http()->createRequest('GET', $this->uri . '/camera/snapshot'), $this->clientOption)->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}