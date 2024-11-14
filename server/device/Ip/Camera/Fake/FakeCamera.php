<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera\Fake;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Framework\Http\Stream;

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

    protected function getScreenshotInternal(): Stream
    {
        throw new DeviceException($this, '[Fake] Не удалось получить скриншот');
    }
}