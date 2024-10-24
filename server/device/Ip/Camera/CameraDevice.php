<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\IpDevice;
use Selpol\Framework\Http\Stream;
use Selpol\Framework\Http\Uri;

abstract class CameraDevice extends IpDevice
{
    public function __construct(Uri $uri, string $password, public CameraModel $model, ?int $id = null)
    {
        parent::__construct($uri, $password, $id);

        $this->setLogger(file_logger('camera'));
    }

    public function getScreenshot(): Stream
    {
        throw new DeviceException($this, 'Не удалось получить скриншот');
    }
}