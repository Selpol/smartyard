<?php

use backends\backend;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Logger\FileLogger;
use Selpol\Service\BackendService;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorException;

if (!function_exists('logger')) {
    function logger(string $channel): LoggerInterface
    {
        return FileLogger::channel($channel);
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