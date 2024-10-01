<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

class Config
{
    /**
     * @var array<string, string>
     */
    private array $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * @return array<string, string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function load(string $values): void
    {
        $lines = explode(PHP_EOL, $values);

        $group = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line == '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, '[') && str_ends_with($line, ']')) {
                $group = trim(substr($line, 1, -1));

                if ($group != '') {
                    $group .= '.';
                }

                continue;
            }

            $segments = explode('=', $line, 2);

            if (count($segments) == 2) {
                $key = trim($segments[0]);
                $value = trim($segments[1]);

                if ($key != '' && $value != '') {
                    $this->values[$group . $key] = $value;
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