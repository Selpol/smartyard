<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\HikVision;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Trait\HikVisionTrait;
use Selpol\Framework\Http\Stream;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;
use Throwable;

class HikVisionCamera extends CameraDevice
{
    use HikVisionTrait;

    public string $login = 'admin';

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, CameraModel $model)
    {
        parent::__construct($uri, $password, $model);

        $this->clientOption->digest($this->login, $this->password);
    }

    public function getSysInfo(): array
    {
        try {
            $info = $this->get('/ISAPI/System/deviceInfo');

            return [
                'DeviceID' => $info['deviceID'],
                'DeviceModel' => $info['model'],
                'HardwareVersion' => $info['hardwareVersion'],
                'SoftwareVersion' => $info['firmwareVersion'] . ' ' . $info['firmwareReleasedDate']
            ];
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить информацию об устройстве', $throwable->getMessage(), previous: $throwable);
        }
    }

    public function getScreenshot(): Stream
    {
        try {
            return $this->client->send(request('GET', $this->uri . '/Streaming/channels/101/picture?snapShotImageType=JPEG'), $this->clientOption)->getBody();
        } catch (Throwable $throwable) {
            throw new DeviceException($this, 'Не удалось получить скриншот', $throwable->getMessage(), previous: $throwable);
        }
    }
}