<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Dvr;

use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class DvrDevice extends IpDevice
{
    public DvrModel $model;

    public function __construct(Uri $uri, string $login, #[SensitiveParameter] string $password, DvrModel $model)
    {
        $this->login = $login;

        parent::__construct($uri, $password);

        $this->model = $model;
    }

    public function getCameras(): array
    {
        return [];
    }

    public function getCameraId(string $query): ?string
    {
        return null;
    }

    public function addCamera(DeviceCamera $camera): bool
    {
        return false;
    }

    public function updateCamera(DeviceCamera $camera): bool
    {
        return false;
    }

    public function removeCamera(DeviceCamera $camera): bool
    {
        return false;
    }
}