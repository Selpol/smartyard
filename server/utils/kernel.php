<?php declare(strict_types=1);

if (!function_exists('config_get')) {
    function config_get(?string $key = null, mixed $default = null): mixed
    {
        if ($key !== null) {
            $config = kernel()->getConfig();

            return collection_get($config, $key, $default);
        }

        return kernel()->getConfig();
    }
}