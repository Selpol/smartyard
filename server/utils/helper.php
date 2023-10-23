<?php declare(strict_types=1);

use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Exception\ValidatorException;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorOnItemInterface;

if (!function_exists('task')) {
    function task(Task $task): TaskContainer
    {
        return new TaskContainer($task);
    }
}

if (!function_exists('camera')) {
    function camera(int $id): ?CameraDevice
    {
        return container(DeviceService::class)->cameraById($id);
    }
}

if (!function_exists('intercom')) {
    function intercom(int $id): ?IntercomDevice
    {
        return container(DeviceService::class)->intercomById($id);
    }
}

if (!function_exists('validate')) {
    /**
     * @param string $name
     * @param mixed $value
     * @param ValidatorOnItemInterface|array<ValidatorOnItemInterface> $items
     * @return mixed
     * @throws ValidatorException
     */
    function validate(string $name, mixed $value, ValidatorOnItemInterface|array $items): mixed
    {
        if (!is_array($items))
            $items = [$items];

        $result = $value;

        foreach ($items as $item)
            $result = $item->onItem($name, [$name => $value]);

        return $result;
    }
}

if (!function_exists('validator')) {
    /**
     * @param array $value
     * @param array<string, ValidatorOnItemInterface|array<ValidatorOnItemInterface>> $items
     * @return array
     * @throws ValidatorException
     */
    function validator(array $value, array $items): array
    {
        foreach ($items as $key => $item)
            if (!is_array($item))
                $items[$key] = [$item];

        $validator = new Validator($value, $items);

        return $validator->validate();
    }
}

if (!function_exists('rule')) {
    function rule(): Rule
    {
        return new Rule();
    }
}

if (!function_exists('filter')) {
    function filter(): Filter
    {
        return new Filter();
    }
}