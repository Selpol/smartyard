<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\HikVision;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Http\Stream;
use Throwable;

class HikVisionCamera extends CameraDevice
{
    public string $login = 'admin';

    public function getSysInfo(): array
    {
        try {
            $info = $this->get('/System/deviceInfo');

            return [
                'DeviceID' => $info['deviceID'],
                'DeviceModel' => $info['model'],
                'HardwareVersion' => $info['hardwareVersion'],
                'SoftwareVersion' => $info['firmwareVersion'] . ' ' . $info['firmwareReleasedDate']
            ];
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }

    public function getScreenshot(): Stream
    {
        try {
            return $this->client()->get($this->uri . '/Streaming/channels/101/picture?snapShotImageType=JPEG', ['Authorization' => 'Basic ' . base64_encode($this->login . ':' . $this->password)])->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, message: $throwable->getMessage(), previous: $throwable);
        }
    }
}