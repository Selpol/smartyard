<?php

namespace Selpol\Kernel\Trait;

use RuntimeException;

trait EnvTrait
{
    private array $env;

    public function getEnv(): array
    {
        return $this->env;
    }

    public function getEnvValue(string $key, ?string $default = null): mixed
    {
        $value = getenv($key);

        if ($value !== false)
            return $value;

        if (array_key_exists($key, $this->env))
            return $this->env[$key];

        return $default;
    }

    public function setEnv(array $env): void
    {
        $this->env = $env;
    }

    private function loadEnv(bool $cache = true): void
    {
        if ($cache && file_exists(path('var/cache/env.php')))
            $this->setEnv(require path('var/cache/env.php'));
        else if (file_exists(path('.env'))) {
            $content = file_get_contents(path('.env'));
            $lines = explode(PHP_EOL, $content);

            $env = [];

            for ($i = 0; $i < count($lines); $i++) {
                if (str_starts_with($lines[$i], '#') || !str_contains($lines[$i], '=')) continue;

                $value = explode('=', $lines[$i], 2);

                if (count($value) == 2)
                    $env[trim($value[0])] = trim($value[1]);
            }

            $this->setEnv($env);
        } else throw new RuntimeException('Env is not loaded');
    }
}