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

        $groups = [];
        $reset = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line == '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, '[') && str_ends_with($line, ']')) {
                if ($reset) {
                    $groups = [];
                }

                $reset = false;

                $group = trim(substr($line, 1, -1));

                if ($group != '') {
                    $groups[] = $group;
                }

                continue;
            }

            $segments = explode('=', $line, 2);

            if (count($segments) == 2) {
                $reset = true;

                $key = trim($segments[0]);
                $value = trim($segments[1]);

                if ($key != '' && $value != '') {
                    if (count($groups) > 0) {
                        foreach ($groups as $group) {
                            $this->values[$group . '.' . $key] = $value;
                        }
                    } else {
                        $this->values[$key] = $value;
                    }
                }
            }
        }
    }

    public function set(ConfigKey|string $key, string $value): Config
    {
        $key = is_string($key) ? $key : $key->value;

        $this->values[$key] = $value;

        return $this;
    }

    public function scope(string $value): Config
    {
        $config = clone $this;
        $config->load($value);

        return $config;
    }

    public function use(string $value, callable $callback): Config
    {
        $config = $this->scope($value);

        $callback($config);

        return $this;
    }

    public function resolve(ConfigKey|string $key, ?string $default = null): ?string
    {
        $key = is_string($key) ? $key : $key->value;

        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $default;
    }

    public function __toString(): string
    {
        $result = '';

        foreach ($this->values as $key => $value) {
            $result .= $key . '=' . $value . PHP_EOL;
        }

        return $result;
    }
}