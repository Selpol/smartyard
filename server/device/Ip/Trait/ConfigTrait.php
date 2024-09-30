<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Trait;

use Selpol\Feature\Config\Config;

trait ConfigTrait
{
    protected Config $config;

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
}