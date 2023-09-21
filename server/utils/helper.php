<?php

use backends\backend;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Selpol\Cache\RedisCache;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Kernel\Kernel;
use Selpol\Logger\FileLogger;
use Selpol\Service\BackendService;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorException;
use Selpol\Validator\ValidatorMessage;

if (!function_exists('path')) {
    function path(string $value): string
    {
        return dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . $value;
    }
}

if (!function_exists('logger')) {
    function logger(string $channel): LoggerInterface
    {
        return FileLogger::channel($channel);
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

if (!function_exists('backend')) {
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function backend(string $backend, bool $login = false): backend|false
    {
        return container(BackendService::class)->get($backend, $login);
    }
}

if (!function_exists('task')) {
    function task(Task $task): TaskContainer
    {
        return new TaskContainer($task);
    }
}

if (!function_exists('camera')) {
    /**
     * @throws NotFoundExceptionInterface
     */
    function camera(string $model, string $url, string $password): CameraDevice|false
    {
        return container(DeviceService::class)->camera($model, $url, $password);
    }
}

if (!function_exists('intercom')) {
    /**
     * @throws NotFoundExceptionInterface
     */
    function intercom(string $model, string $url, string $password): IntercomDevice|false
    {
        return container(DeviceService::class)->intercom($model, $url, $password);
    }
}

if (!function_exists('validator')) {
    /**
     * @param array $value
     * @param array $items
     * @return array
     * @throws \Selpol\Http\HttpException
     */
    function validator(array $value, array $items): array
    {
        $validator = new Validator($value, $items);

        try {
            return $validator->validate();
        } catch (ValidatorException $e) {
            throw new \Selpol\Http\HttpException(message: $e->getValidatorMessage()->getMessage(), code: 400);
        }
    }
}

if (!function_exists('redis_cache')) {
    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    function redis_cache(string $key, callable $default, DateInterval|int|null $ttl = null): mixed
    {
        $cache = container(RedisCache::class);

        $value = $cache->get($key);

        if ($value !== null)
            return $value;

        $value = call_user_func($default);

        $cache->set($key, $value, $ttl);

        return $value;
    }
}
