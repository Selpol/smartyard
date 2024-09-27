<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

class Config
{
    /**
     * @var array<string, string>
     */
    private array $values = [];

    public function load(string $value): void
    {
        $lines = explode(PHP_EOL, $value);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line == '' || str_starts_with($line, '#')) {
                continue;
            }

            $segments = explode('=', $line, 2);

            if (count($segments) == 2) {
                $this->values[$segments[0]] = $segments[1];
            }
        }
    }

    public function string(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $default;
    }

    public function bool(string $key, ?bool $default = null): ?bool
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key] == '1' || $this->values[$key] == 'true';
        }

        return $default;
    }

    public function int(string $key, ?int $default = null): ?int
    {
        if (array_key_exists($key, $this->values)) {
            return intval($this->values[$key]);
        }

        return $default;
    }

    public function float(string $key, ?float $default = null): float
    {
        if (array_key_exists($key, $this->values)) {
            return floatval($this->values[$key]);
        }

        return $default;
    }

    public function clear(): void
    {
        $this->values = [];
    }
}