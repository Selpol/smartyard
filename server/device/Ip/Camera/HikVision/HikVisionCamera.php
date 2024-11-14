<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\HikVision;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Trait\HikVisionTrait;
use Selpol\Framework\Http\Stream;
use Throwable;

class HikVisionCamera extends CameraDevice
{
    use HikVisionTrait;

    public string $login = 'admin';

    protected function getScreenshotInternal(): Stream
    {
        try {
            return $this->client->send(client_request('GET', $this->uri . '/Streaming/channels/101/picture?snapShotImageType=JPEG'), $this->clientOption)->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить скриншот', $throwable->getMessage(), previous: $throwable);
        }
    }
}