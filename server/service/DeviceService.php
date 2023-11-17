<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\DvrModel;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

#[Singleton]
readonly class DeviceService
{
    public function cameraById(int $id): ?CameraDevice
    {
        if ($camera = container(CameraFeature::class)->getCamera($id))
            return $this->camera($camera['model'], $camera['url'], $camera['credentials']);

        return null;
    }

    public function camera(string $model, string $url, #[SensitiveParameter] string $password): ?CameraDevice
    {
        $models = CameraModel::models();

        if (array_key_exists($model, $models))
            return new $models[$model]->class(new Uri($url), $password, $models[$model]);

        return null;
    }

    public function intercomById(int $id): ?IntercomDevice
    {
        if ($deviceIntercom = DeviceIntercom::findById($id, setting: setting()->columns(['model', 'url', 'credentials'])->nonNullable()))
            return $this->intercom($deviceIntercom->model, $deviceIntercom->url, $deviceIntercom->credentials);

        return null;
    }

    public function intercom(string $model, string $url, #[SensitiveParameter] string $password): ?IntercomDevice
    {
        $models = IntercomModel::models();

        if (array_key_exists($model, $models))
            return new $models[$model]->class(new Uri($url), $password, $models[$model]);

        return null;
    }

    public function dvrById(int $id): ?DvrDevice
    {
        if ($dvrServer = DvrServer::findById($id, setting: setting()->nonNullable())) {
            $credentials = $dvrServer->credentials();

            return $this->dvr($dvrServer->type, $dvrServer->url, $credentials['username'], $credentials['password']);
        }

        return null;
    }

    public function dvr(string $model, string $url, string $login, #[SensitiveParameter] string $password): ?DvrDevice
    {
        if ($model = DvrModel::model($model))
            return new $model->class(new Uri($url), $login, $password, $model);

        return null;
    }
}