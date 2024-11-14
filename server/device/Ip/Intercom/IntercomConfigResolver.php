<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigResolver;

class IntercomConfigResolver extends ConfigResolver
{
    private IntercomModel $model;

    private DeviceIntercom $intercom;

    public function __construct(Config $config, IntercomModel $model, DeviceIntercom $intercom)
    {
        parent::__construct($config);

        $this->model = $model;
        $this->intercom = $intercom;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        $value = $this->config->resolve($key);

        if ($value != null) {
            return $value;
        }

        // Глобальная конфигурация по модели устройства
        if ($this->intercom->device_model) {
            $model = strtoupper(str_replace('.', '', $this->intercom->device_model));

            $value = $this->config->resolve('intercom.' . $this->model->vendor . '.' . $model . '.' . $key);

            if ($value != null) {
                return $value;
            }

            if (str_contains($model, '_REV')) {
                $segments = explode('_REV', $model);

                if (count($segments) > 1) {
                    $value = $this->config->resolve('intercom.' . $this->model->vendor . '.' . $segments[0] . '.' . $key);

                    if ($value != null) {
                        return $value;
                    }
                }
            }
        }

        // Глобальная конфигурация по производителю модели устройства и названию модели
        $value = $this->config->resolve('intercom.' . $this->model->vendor . '.' . str_replace('.', '', $this->model->title));

        if ($value != null) {
            return $value;
        }

        // Глобальная конфигурация по производителю модели устройства
        $value = $this->config->resolve('intercom.' . $this->model->vendor . '.' . $key);

        if ($value != null) {
            return $value;
        }

        // Глобальная конфигурация устройства
        return $this->config->resolve('intercom.' . $key, $default);
    }
}