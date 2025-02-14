<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Cli\Cron\CronEnum;
use Selpol\Cli\Cron\CronInterface;
use Selpol\Device\Ip\Camera\CameraConfigResolver;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\DvrModel;
use Selpol\Device\Ip\Intercom\IntercomConfigResolver;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Feature\Config\ConfigResolver;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Http\Uri;
use Throwable;

#[Singleton]
class DeviceService implements CronInterface
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

    public function cron(CronEnum $value): bool
    {
        if ($value == CronEnum::daily) {
            $deviceIntercoms = DeviceIntercom::fetchAll();

            foreach ($deviceIntercoms as $deviceIntercom) {
                $intercom = $this->intercomByEntity($deviceIntercom);

                if (!$intercom->ping()) {
                    continue;
                }

                $info = $intercom->getSysInfo();

                $deviceIntercom->device_id = $info->deviceId;
                $deviceIntercom->device_model = $info->deviceModel;
                $deviceIntercom->device_software_version = $info->softwareVersion;
                $deviceIntercom->device_hardware_version = $info->hardwareVersion;

                $deviceIntercom->update();
            }
        }

        return true;
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

        if (($camera = DeviceCamera::findById($id, setting: setting()->nonNullable())) instanceof DeviceCamera) {
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

        if (($model = CameraModel::model($camera->model)) instanceof CameraModel) {
            $feature = container(ConfigFeature::class);

            if ($this->cache) {
                try {
                    $config = $feature->getCacheConfigForCamera($camera->camera_id);

                    if ($config === null) {
                        $config = $feature->getOptimizeConfigForCamera($model, $camera);
                        $feature->setCacheConfigForCamera($config, $camera->camera_id);
                    }

                    $resolver = new ConfigResolver($config);
                } catch (Throwable) {
                    $resolver = new CameraConfigResolver($feature->getConfigForCamera($model, $camera), $model, $camera);
                }
            } else {
                $resolver = new CameraConfigResolver($feature->getConfigForCamera($model, $camera), $model, $camera);
            }

            $device = $model->instance($camera, $resolver);

            if ($this->cache) {
                $this->cameras[$id] = $device;
            }

            return $device;
        }

        return null;
    }

    public function intercomById(int $id): ?IntercomDevice
    {
        if ($this->cache && array_key_exists($id, $this->intercoms)) {
            return $this->intercoms[$id];
        }

        if (($intercom = DeviceIntercom::findById($id, setting: setting()->nonNullable())) instanceof DeviceIntercom) {
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

        if (($model = IntercomModel::model($intercom->model)) instanceof IntercomModel) {
            $feature = container(ConfigFeature::class);

            if ($this->cache) {
                try {
                    $config = $feature->getCacheConfigForIntercom($intercom->house_domophone_id);

                    if ($config === null) {
                        $config = $feature->getOptimizeConfigForIntercom($model, $intercom);
                        $feature->setCacheConfigForIntercom($config, $intercom->house_domophone_id);
                    }

                    $resolver = new ConfigResolver($config);
                } catch (Throwable) {
                    $resolver = new IntercomConfigResolver($feature->getConfigForIntercom($model, $intercom), $model, $intercom);
                }
            } else {
                $resolver = new IntercomConfigResolver($feature->getConfigForIntercom($model, $intercom), $model, $intercom);
            }

            $device = $model->instance($intercom, $resolver);

            if ($this->cache) {
                $this->intercoms[$id] = $device;
            }

            return $device;
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

        if (($model = DvrModel::model($server->type)) instanceof DvrModel) {
            $dvr = new $model->class(new Uri($server->url), $credentials['username'], $credentials['password'], $model, $server);

            if ($this->cache) {
                $this->dvrs[$id] = $dvr;
            }

            return $dvr;
        }

        return null;
    }
}