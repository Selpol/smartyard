<?php declare(strict_types=1);

use Selpol\Device\Ip\Camera\CameraDevice;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Service\DeviceService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;

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

if (!function_exists('dvr')) {
    function dvr(int $id): ?DvrDevice
    {
        return container(DeviceService::class)->dvrById($id);
    }
}