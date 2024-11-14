<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Framework\Cache\FileCache;
use Throwable;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function getCacheConfigForIntercom(int $id): ?Config
    {
        try {
            $values = container(FileCache::class)->get('intercom.config.' . $id);

            if ($values) {
                return new Config($values);
            }
        } catch (Throwable $throwable) {
            file_logger('config')->error($throwable);
        }

        return null;
    }

    public function getCacheConfigForCamera(int $id): ?Config
    {
        try {
            $values = container(FileCache::class)->get('camera.config.' . $id);

            if ($values) {
                return new Config($values);
            }
        } catch (Throwable $throwable) {
            file_logger('config')->error($throwable);
        }

        return null;
    }

    public function setCacheConfigForIntercom(Config $config, int $id): void
    {
        try {
            container(FileCache::class)->set('intercom.config.' . $id, $config->getValues());
        } catch (Throwable $throwable) {
            file_logger('config')->error($throwable);
        }
    }

    public function setCacheConfigForCamera(Config $config, int $id): void
    {
        try {
            container(FileCache::class)->set('camera.config.' . $id, $config->getValues());
        } catch (Throwable $throwable) {
            file_logger('config')->error($throwable);
        }
    }

    public function clearCacheConfigForIntercom(?int $id = null): void
    {
        try {
            $cache = container(FileCache::class);

            if ($id !== null) {
                $cache->delete('intercom.config.' . $id);

                return;
            }

            $files = scandir(path('var/cache'));

            if ($files) {
                foreach ($files as $file) {
                    if (str_starts_with($file, 'intercom.config.') && str_ends_with($file, '.php')) {
                        $cache->delete(substr($file, 0, -4));
                    }
                }
            }
        } catch (Throwable $throwable) {
            file_logger('config')->error($throwable);
        }
    }

    public function clearCacheConfigForCamera(?int $id = null): void
    {
        try {
            $cache = container(FileCache::class);

            if ($id !== null) {
                $cache->delete('intercom.camera.' . $id);

                return;
            }

            $files = scandir(path('var/cache'));

            if ($files) {
                foreach ($files as $file) {
                    if (str_starts_with($file, 'intercom.camera.') && str_ends_with($file, '.php')) {
                        $cache->delete(substr($file, 0, -4));
                    }
                }
            }
        } catch (Throwable $throwable) {
            file_logger('config')->error($throwable);
        }
    }

    public function getConfigForIntercom(IntercomModel $model, DeviceIntercom $intercom): Config
    {
        $value = new Config();

        $coreVar = container(CoreVarRepository::class)->findByName('intercom.config');

        if ($coreVar && $coreVar->var_value) {
            $value->load($coreVar->var_value);
        }

        if ($model->config) {
            $value->load($model->config);
        }

        if ($intercom->config) {
            $value->load($intercom->config);
        }

        return $value;
    }

    public function getConfigForCamera(CameraModel $model, DeviceCamera $camera): Config
    {
        $value = new Config();

        $coreVar = container(CoreVarRepository::class)->findByName('camera.config');

        if ($coreVar && $coreVar->var_value) {
            $value->load($coreVar->var_value);
        }

        if ($model->config) {
            $value->load($model->config);
        }

        return $value;
    }

    public function getOptimizeConfigForIntercom(IntercomModel $model, DeviceIntercom $intercom): Config
    {
        $config = $this->getConfigForIntercom($model, $intercom);

        $values = $config->getValues();
        $keys = array_keys($values);

        $intercoms = [];
        $vendors = [];
        $titles = [];
        $revs = [];
        $locals = [];

        foreach ($keys as $key) {
            $segments = explode('.', $key);

            if ($segments[0] !== 'intercom') {
                $locals[$key] = $values[$key];

                continue;
            }

            if (count($segments) == 1) {
                continue;
            }

            if (strtoupper($segments[1]) !== $segments[1]) {
                $intercoms[implode('.', array_slice($segments, 1))] = $values[$key];

                continue;
            }

            if ($segments[1] !== $model->vendor || count($segments) <= 2) {
                continue;
            }

            if (strtoupper($segments[2]) !== $segments[2]) {
                $vendors[implode('.', array_slice($segments, 2))] = $values[$key];

                continue;
            }

            if ($segments[2] == $model->title) {
                $titles[implode('.', array_slice($segments, 2))] = $values[$key];

                continue;
            }

            if ($intercom->device_model) {
                if ($segments[2] == strtoupper($intercom->device_model)) {
                    $revs[implode('.', array_slice($segments, 2))] = $values[$key];
                }

                if (str_contains($intercom->device_model, '_rev')) {
                    $models = explode('_rev', $intercom->device_model);

                    if ($segments[2] == strtoupper($models[0])) {
                        $revs[implode('.', array_slice($segments, 3))] = $values[$key];
                    }
                }
            }
        }

        return new Config(array_merge($intercoms, $vendors, $titles, $revs, $locals));
    }

    public function getOptimizeConfigForCamera(CameraModel $model, DeviceCamera $camera): Config
    {
        $config = $this->getConfigForCamera($model, $camera);

        $values = $config->getValues();
        $keys = array_keys($values);

        $cameras = [];
        $vendors = [];
        $titles = [];
        $revs = [];
        $locals = [];

        foreach ($keys as $key) {
            $segments = explode('.', $key);

            if ($segments[0] !== 'camera') {
                $locals[$key] = $values[$key];

                continue;
            }

            if (count($segments) == 1) {
                continue;
            }

            if (strtoupper($segments[1]) !== $segments[1]) {
                $cameras[implode('.', array_slice($segments, 1))] = $values[$key];

                continue;
            }

            if ($segments[1] !== $model->vendor || count($segments) <= 2) {
                continue;
            }

            if (strtoupper($segments[2]) !== $segments[2]) {
                $vendors[implode('.', array_slice($segments, 2))] = $values[$key];

                continue;
            }

            if ($segments[2] == $model->title) {
                $titles[implode('.', array_slice($segments, 2))] = $values[$key];

                continue;
            }
        }

        return new Config(array_merge($cameras, $vendors, $titles, $revs, $locals));
    }
}