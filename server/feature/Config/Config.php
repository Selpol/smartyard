<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

class Config
{
    /**
     * @var array<string, string>
     */
    private array $values = [];

    public function getKeys(): array
    {
        return array_keys($this->values);
    }

    public function load(string $values): void
    {
        $lines = explode(PHP_EOL, $values);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line == '' || str_starts_with($line, '#')) {
                continue;
            }

            $segments = explode('=', $line, 2);

            if (count($segments) == 2) {
                $key = trim($segments[0]);
                $value = trim($segments[1]);

                if ($key != '' && $value != '') {
                    $this->values[$key] = $value;
                }
            }
        }
    }

    public function resolve(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $default;
    }
}