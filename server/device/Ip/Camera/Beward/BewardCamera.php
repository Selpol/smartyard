<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Beward;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Trait\BewardTrait;
use Selpol\Framework\Http\Stream;
use Throwable;

class BewardCamera extends CameraDevice
{
    use BewardTrait;

    public string $login = 'admin';

    protected function getScreenshotInternal(): Stream
    {
        try {
            return $this->client->send(client_request('GET', $this->uri . '/cgi-bin/images_cgi?channel=0'), $this->clientOption)->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить скриншот', $throwable->getMessage(), previous: $throwable);
        }
    }
}