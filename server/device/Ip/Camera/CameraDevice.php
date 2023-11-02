<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Device\Exception\DeviceException;
use Selpol\Device\Ip\IpDevice;
use Selpol\Framework\Http\Stream;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class CameraDevice extends IpDevice
{
    public CameraModel $model;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, CameraModel $model)
    {
        parent::__construct($uri, $password);

        $this->model = $model;
    }

    public function getScreenshot(): Stream
    {
        throw new DeviceException($this, 'Не удалось получить скриншот');
    }
}