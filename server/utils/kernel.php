<?php

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Kernel\Kernel;

if (!function_exists('path')) {
    function path(string $value): string
    {
        return dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . $value;
    }
}

if (!function_exists('kernel')) {
    function kernel(): ?Kernel
    {
        return Kernel::instance();
    }
}

if (!function_exists('env')) {
    function env(?string $key = null, ?string $default = null): mixed
    {
        if ($key !== null)
            return kernel()->getEnvValue($key, $default);

        return kernel()->getEnv();
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key !== null)
            return kernel()->getConfigValue($key, $default);

        return kernel()->getConfig();
    }
}

if (!function_exists('container')) {
    /**
     * @template T
     * @psalm-param class-string<T> $key
     * @return T
     * @throws NotFoundExceptionInterface
     */
    function container(string $key): mixed
    {
        return kernel()->getContainerValue($key);
    }
}