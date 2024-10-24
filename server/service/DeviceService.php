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
    private array $cameras = [];

    /** @var array<int, IntercomDevice> */
    private array $intercoms = [];

    /** @var array<int, DvrDevice> */
    private array $dvrs = [];

    private bool $cache = true;

    public function __construct()
    {
    }

    public function disableCache(): void
    {
        $this->cache = false;
    }

    public function cameraById(int $id): ?CameraDevice
    {
        if ($this->cache && array_key_exists($id, $this->cameras)) {
            return $this->cameras[$id];
        }

        if (($camera = DeviceCamera::findById($id, setting: setting()->columns(['camera_id', 'model', 'url', 'credentials'])->nonNullable())) instanceof DeviceCamera) {
            return $this->cameraByEntity($camera);
        }

        return null;
    }

    public function cameraByEntity(DeviceCamera $camera): ?CameraDevice
    {
        $id = $camera->camera_id;

        if ($this->cache && array_key_exists($id, $this->cameras)) {
            return $this->cameras[$id];
        }

        $camera = $this->camera($camera->model, $camera->url, $camera->credentials, $camera->camera_id);

        if ($this->cache) {
            $this->cameras[$id] = $camera;
        }

        return $camera;
    }

    public function camera(string $model, string $url, #[SensitiveParameter] string $password, ?int $id = null): ?CameraDevice
    {
        if (($model = CameraModel::model($model)) instanceof CameraModel) {
            return new $model->class(new Uri($url), $password, $model, $id);
        }

        return null;
    }

    public function intercomById(int $id): ?IntercomDevice
    {
        if ($this->cache && array_key_exists($id, $this->intercoms)) {
            return $this->intercoms[$id];
        }

        if (($intercom = DeviceIntercom::findById($id, setting: setting()->columns(['house_domophone_id', 'model', 'url', 'credentials'])->nonNullable())) instanceof DeviceIntercom) {
            return $this->intercomByEntity($intercom);
        }

        return null;
    }

    public function intercomByEntity(DeviceIntercom $intercom): ?IntercomDevice
    {
        $id = $intercom->house_domophone_id;

        if ($this->cache && array_key_exists($id, $this->intercoms)) {
            return $this->intercoms[$id];
        }

        $intercom = $this->intercom($intercom->model, $intercom->url, $intercom->credentials, $intercom->house_domophone_id);

        if ($this->cache) {
            $this->intercoms[$id] = $intercom;
        }

        return $intercom;
    }

    public function intercom(string $model, string $url, #[SensitiveParameter] string $password, ?int $id = null): ?IntercomDevice
    {
        if (($model = IntercomModel::model($model)) instanceof IntercomModel) {
            return new $model->class(new Uri($url), $password, $model, $id);
        }

        return null;
    }

    public function dvrById(int $id): ?DvrDevice
    {
        if ($this->cache && array_key_exists($id, $this->dvrs)) {
            return $this->dvrs[$id];
        }

        if (($server = DvrServer::findById($id, setting: setting()->nonNullable())) instanceof DvrServer) {
            return $this->dvrByEntity($server);
        }

        return null;
    }

    public function dvrByEntity(DvrServer $server): ?DvrDevice
    {
        $id = $server->id;

        if ($this->cache && array_key_exists($id, $this->dvrs)) {
            return $this->dvrs[$id];
        }

        if (!$server->credentials) {
            return null;
        }

        $credentials = $server->credentials();

        $dvr = $this->dvr($server->type, $server->url, $credentials['username'], $credentials['password'], $server);

        if ($this->cache) {
            $this->dvrs[$id] = $dvr;
        }

        return $dvr;
    }

    public function dvr(string $model, string $url, string $login, #[SensitiveParameter] string $password, DvrServer $server): ?DvrDevice
    {
        if (($model = DvrModel::model($model)) instanceof DvrModel) {
            return new $model->class(new Uri($url), $login, $password, $model, $server, $server->id);
        }

        return null;
    }
}