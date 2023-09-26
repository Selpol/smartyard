<?php declare(strict_types=1);

namespace Selpol\Kernel\Trait;

use RuntimeException;

trait ConfigTrait
{
    private array $config;

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->config))
            return $this->config[$key];

        if (str_contains($key, '.')) {
            $segments = explode('.', $key);

            $items = &$this->config;

            foreach ($segments as $segment) {
                if (!is_array($items) || !array_key_exists($segment, $items))
                    return $default;

                $items = &$items[$segment];
            }

            return $items;
        }

        return $default;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    private function loadConfig(bool $cache = true): void
    {
        if ($cache && file_exists(path('var/cache/config.php')))
            $this->setConfig(require path('var/cache/config.php'));
        else if (file_exists(path('config/config.php')))
            $this->setConfig(require path('config/config.php'));
        else throw new RuntimeException('Config is not loaded');
    }
}