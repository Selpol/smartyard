<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

class ConfigResolver
{
    public Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        return $this->config->resolve($key, $default);
    }

    public function float(string $key, ?float $default = null): ?float
    {
        $value = $this->string($key);

        if ($value == null) {
            return $default;
        }

        return floatval($value);
    }

    public function int(string $key, ?int $default = null): ?int
    {
        $value = $this->string($key);

        if ($value == null) {
            return $default;
        }

        return intval($value);
    }

    public function bool(string $key, ?bool $default = null): ?bool
    {
        $value = $this->string($key);

        if ($value == null) {
            return $default;
        }

        return $value == '1' || $value == 'true';
    }
}