<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Config;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class IntercomDevice extends IpDevice
{
    public function __construct(Uri $uri, #[SensitiveParameter] string $password, public IntercomModel $model, private readonly DeviceIntercom $intercom, private readonly Config $config)
    {
        parent::__construct($uri, $password);

        match ($this->resolveString('auth', 'basic')) {
            "any_safe" => $this->clientOption->anySafe($this->login, $password),
            "basic" => $this->clientOption->basic($this->login, $password),
            "digest" => $this->clientOption->digest($this->login, $password),
        };

        if (!$this->debug) {
            $this->debug = $this->resolveBool('debug', false);
        }

        $this->setLogger(file_logger('intercom'));
    }

    public function resolveString(string $key, ?string $default = null): ?string
    {
        $default = $this->config->resolve($key, $default);

        if ($default != null) {
            return $default;
        }

        $default = $this->config->resolve('intercom.' . $this->intercom->house_domophone_id . '.' . $key, $default);

        if ($default != null) {
            return $default;
        }

        if ($this->intercom->device_model) {
            $default = $this->config->resolve('intercom.' . $this->intercom->device_model . '.' . $key, $default);

            if ($default != null) {
                return $default;
            }
        }

        $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . $key, $default);

        if ($default != null) {
            return $default;
        }

        $default = $this->config->resolve('intercom.' . $this->model->title . '.' . $key, $default);

        if ($default != null) {
            return $default;
        }

        return $this->config->resolve('intercom.' . $key, $default);
    }

    public function resolveBool(string $key, ?bool $default = null): ?bool
    {
        $value = $this->resolveString($key);

        if ($value == null) {
            return $default;
        }

        return $value == '1' || $value == 'true';
    }

    public function resolveInt(string $key, ?int $default = null): ?int
    {
        $value = $this->resolveString($key);

        if ($value == null) {
            return $default;
        }

        return intval($value);
    }

    public function resolveFloat(string $key, ?float $default = null): ?float
    {
        $value = $this->resolveString($key);

        if ($value == null) {
            return $default;
        }

        return floatval($value);
    }

    public function open(int $value): void
    {
    }

    public function call(int $apartment): void
    {
    }

    public function callStop(): void
    {
    }

    public function reboot(): void
    {
    }

    public function reset(): void
    {
    }
}