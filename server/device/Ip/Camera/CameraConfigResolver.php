<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Camera;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigResolver;

class CameraConfigResolver extends ConfigResolver
{
    private CameraModel $model;

    private DeviceCamera $camera;

    public function __construct(Config $config, CameraModel $model, DeviceCamera $camera)
    {
        parent::__construct($config);

        $this->model = $model;
        $this->camera = $camera;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        $value = $this->config->resolve($key);

        if ($value != null) {
            return $value;
        }

        // Глобальная конфигурация по производителю модели устройства и названию модели
        $value = $this->config->resolve('camera.' . $this->model->vendor . '.' . str_replace('.', '', $this->model->title));

        if ($value != null) {
            return $value;
        }

        // Глобальная конфигурация по производителю модели устройства
        $value = $this->config->resolve('camera.' . $this->model->vendor . '.' . $key);

        if ($value != null) {
            return $value;
        }

        // Глобальная конфигурация устройства
        return $this->config->resolve('camera.' . $key, $default);
    }
}