<?php declare(strict_types=1);

use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Logger\FileLogger;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Exception\ValidatorException;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorItem;

if (!function_exists('logger')) {
    function logger(string $channel): LoggerInterface
    {
        return FileLogger::channel($channel);
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
    function camera(int $id): ?CameraDevice
    {
        return container(DeviceService::class)->cameraById($id);
    }
}

if (!function_exists('intercom')) {
    /**
     * @throws NotFoundExceptionInterface
     */
    function intercom(int $id): ?IntercomDevice
    {
        return container(DeviceService::class)->intercomById($id);
    }
}

if (!function_exists('validate')) {
    /**
     * @param string $name
     * @param mixed $value
     * @param array<ValidatorItem> $items
     * @return mixed
     * @throws ValidatorException
     */
    function validate(string $name, mixed $value, array $items): mixed
    {
        $result = $value;

        foreach ($items as $item)
            $result = $item->onItem($name, [$name => $value]);

        return $result;
    }
}

if (!function_exists('validator')) {
    /**
     * @param array $value
     * @param array $items
     * @return array
     * @throws ValidatorException
     */
    function validator(array $value, array $items): array
    {
        $validator = new Validator($value, $items);

        return $validator->validate();
    }
}