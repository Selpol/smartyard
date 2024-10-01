<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\IpDevice;
use Selpol\Device\Ip\Trait\ConfigTrait;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Config;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class IntercomDevice extends IpDevice
{
    use ConfigTrait;

    public readonly bool $mifare;

    public readonly ?string $mifareKey;
    public readonly ?int $mifareSector;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, public IntercomModel $model, private readonly DeviceIntercom $intercom, Config $config)
    {
        parent::__construct($uri, $password);

        $this->config = $config;

        match ($this->resolveString('auth', 'basic')) {
            "any_safe" => $this->clientOption->anySafe($this->login, $password),
            "basic" => $this->clientOption->basic($this->login, $password),
            "digest" => $this->clientOption->digest($this->login, $password),
        };

        if (!$this->debug) {
            $this->debug = $this->resolveBool('debug', false);
        }

        if ($this->resolveBool('mifare', false)) {
            $key = $this->resolveString('mifare.key');
            $sector = $this->resolveString('mifare.sector');

            if ($key && str_starts_with($key, 'ENV_')) {
                $key = env(substr($key, 4));
            }

            if ($sector && str_starts_with($sector, 'ENV_')) {
                $sector = env(substr($sector, 4));
            }

            $this->mifare = $key && $sector;

            $this->mifareKey = $key;
            $this->mifareSector = intval($sector);
        } else {
            $this->mifare = false;

            $this->mifareKey = null;
            $this->mifareSector = null;
        }

        $this->setLogger(file_logger('intercom'));
    }

    public function resolveString(string $key, ?string $default = null): ?string
    {
        $default = $this->config->resolve($key, $default);

        if ($default != null) {
            return $default;
        }

        // Глобальная конфигурация по модели устройства
        if ($this->intercom->device_model) {
            $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . str_replace(' ', '_', $this->intercom->device_model) . '.' . $key, $default);

            if ($default != null) {
                return $default;
            }

            if (str_contains($this->intercom->device_model, '_rev')) {
                $segments = explode('_rev', $this->intercom->device_model);

                if (count($segments) > 1) {
                    $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . str_replace(' ', '_', $segments[0]) . '.' . $key, $default);

                    if ($default != null) {
                        return $default;
                    }
                }
            }
        }

        // Глобальная конфигурация по производителю модели устройства
        $default = $this->config->resolve('intercom.' . $this->model->vendor . '.' . $key, $default);

        if ($default != null) {
            return $default;
        }

        // Глобальная конфигурация устройства
        return $this->config->resolve('intercom.' . $key, $default);
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

    public static function template(string $value, array $values): string
    {
        if (preg_match_all('(%\w+%)', $value, $matches)) {
            foreach ($matches as $match) {
                foreach ($match as $item) {
                    $value = str_replace($item, $values[substr($item, 1, -1)], $value);
                }
            }
        }

        return $value;
    }
}