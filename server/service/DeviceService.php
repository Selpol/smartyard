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
class DeviceService
{
    /** @var array<int, CameraDevice> */
    private array $cameras;

    /** @var array<int, IntercomDevice> */
    private array $intercoms;

    /** @var array<int, DvrDevice> */
    private array $dvrs;

    public function __construct()
    {
        $this->cameras = [];
        $this->intercoms = [];
        $this->dvrs = [];
    }

    public function cameraById(int $id): ?CameraDevice
    {
        if (array_key_exists($id, $this->cameras)) {
            return $this->cameras[$id];
        }

        if ($camera = DeviceCamera::findById($id, setting: setting()->columns(['model', 'url', 'credentials'])->nonNullable())) {
            return $this->cameraByEntity($camera);
        }

        return null;
    }

    public function cameraByEntity(DeviceCamera $camera): ?CameraDevice
    {
        $id = $camera->camera_id;

        if (array_key_exists($id, $this->cameras)) {
            return $this->cameras[$id];
        }

        $camera = $this->camera($camera->model, $camera->url, $camera->credentials);

        return $this->cameras[$id] = $camera;
    }

    public function camera(string $model, string $url, #[SensitiveParameter] string $password): ?CameraDevice
    {
        if ($model = CameraModel::model($model)) {
            return new $model->class(new Uri($url), $password, $model);
        }

        return null;
    }

    public function intercomById(int $id): ?IntercomDevice
    {
        if (array_key_exists($id, $this->intercoms)) {
            return $this->intercoms[$id];
        }

        if ($intercom = DeviceIntercom::findById($id, setting: setting()->columns(['model', 'url', 'credentials'])->nonNullable())) {
            return $this->intercomByEntity($intercom);
        }

        return null;
    }

    public function intercomByEntity(DeviceIntercom $intercom): ?IntercomDevice
    {
        $id = $intercom->house_domophone_id;

        if (array_key_exists($id, $this->intercoms)) {
            return $this->intercoms[$id];
        }

        $intercom = $this->intercom($intercom->model, $intercom->url, $intercom->credentials);

        return $this->intercoms[$id] = $intercom;
    }

    public function intercom(string $model, string $url, #[SensitiveParameter] string $password): ?IntercomDevice
    {
        if ($model = IntercomModel::model($model)) {
            return new $model->class(new Uri($url), $password, $model);
        }

        return null;
    }

    public function dvrById(int $id): ?DvrDevice
    {
        if (array_key_exists($id, $this->dvrs)) {
            return $this->dvrs[$id];
        }

        if ($server = DvrServer::findById($id, setting: setting()->nonNullable())) {
            return $this->dvrByEntity($server);
        }

        return null;
    }

    public function dvrByEntity(DvrServer $server): ?DvrDevice
    {
        $id = $server->id;

        if (array_key_exists($id, $this->dvrs)) {
            return $this->dvrs[$id];
        }

        if (!$server->credentials) {
            return null;
        }

        $credentials = $server->credentials();

        $dvr = $this->dvr($server->type, $server->url, $credentials['username'], $credentials['password'], $server);

        return $this->dvrs[$id] = $dvr;
    }

    public function dvr(string $model, string $url, string $login, #[SensitiveParameter] string $password, DvrServer $server): ?DvrDevice
    {
        if ($model = DvrModel::model($model)) {
            return new $model->class(new Uri($url), $login, $password, $model, $server);
        }

        return null;
    }
}