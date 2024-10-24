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
        $default = $this->config->resolve($key, $default);

        if ($default != null) {
            return $default;
        }

        // Глобальная конфигурация по модели устройства
        if ($this->intercom->device_model) {
            $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . strtoupper(str_replace('.', '', $this->intercom->device_model)) . '.' . $key, $default);

            if ($default != null) {
                return $default;
            }

            if (str_contains($this->intercom->device_model, '_rev')) {
                $segments = explode('_rev', $this->intercom->device_model);

                if (count($segments) > 1) {
                    $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . strtoupper(str_replace('.', '', $segments[0])) . '.' . $key, $default);

                    if ($default != null) {
                        return $default;
                    }
                }
            }
        }

        // Глобальная конфигурация по производителю модели устройства и названию модели
        $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . str_replace('.', '', $this->model->title), $default);

        if ($default != null) {
            return $default;
        }

        // Глобальная конфигурация по производителю модели устройства
        $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . $key, $default);

        if ($default != null) {
            return $default;
        }

        // Глобальная конфигурация устройства
        return $this->config->resolve('intercom.' . $key, $default);
    }
}