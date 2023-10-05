<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Beward;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Trait\BewardTrait;
use Selpol\Http\Stream;
use Throwable;

class BewardCamera extends CameraDevice
{
    use BewardTrait;

    public string $login = 'admin';

    public function getScreenshot(): Stream
    {
        try {
            return $this->get('/cgi-bin/images_cgi')->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}