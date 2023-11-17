<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\DvrModel;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

#[Singleton]
readonly class DeviceService
{
    public function cameraById(int $id): ?CameraDevice
    {
        if ($camera = DeviceCamera::findById($id, setting: setting()->columns(['model', 'url', 'credentials'])->nonNullable()))
            return $this->cameraByEntity($camera);

        return null;
    }

    public function cameraByEntity(DeviceCamera $camera): ?CameraDevice
    {
        return $this->camera($camera->model, $camera->url, $camera->credentials);
    }

    public function camera(string $model, string $url, #[SensitiveParameter] string $password): ?CameraDevice
    {
        if ($model = CameraModel::model($model))
            return new $model->class(new Uri($url), $password, $model);

        return null;
    }

    public function intercomById(int $id): ?IntercomDevice
    {
        if ($intercom = DeviceIntercom::findById($id, setting: setting()->columns(['model', 'url', 'credentials'])->nonNullable()))
            return $this->intercomByEntity($intercom);

        return null;
    }

    public function intercomByEntity(DeviceIntercom $intercom): ?IntercomDevice
    {
        return $this->intercom($intercom->model, $intercom->url, $intercom->credentials);
    }

    public function intercom(string $model, string $url, #[SensitiveParameter] string $password): ?IntercomDevice
    {
        if ($model = IntercomModel::model($model))
            return new $model->class(new Uri($url), $password, $model);

        return null;
    }

    public function dvrById(int $id): ?DvrDevice
    {
        if ($server = DvrServer::findById($id, setting: setting()->nonNullable()))
            return $this->dvrByEntity($server);

        return null;
    }

    public function dvrByEntity(DvrServer $server): ?DvrDevice
    {
        $credentials = $server->credentials();

        return $this->dvr($server->type, $server->url, $credentials['username'], $credentials['password']);
    }

    public function dvr(string $model, string $url, string $login, #[SensitiveParameter] string $password): ?DvrDevice
    {
        if ($model = DvrModel::model($model))
            return new $model->class(new Uri($url), $login, $password, $model);

        return null;
    }
}