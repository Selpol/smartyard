<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Schedule;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $title Заголовок
 *
 * @property-read string $time Время
 * @property-read string $script Скрипт
 * 
 * @property-read ?string $task Класс задачи
 *
 * @property-read int $status Статус
 */
readonly class ScheduleStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->string()->max(256)->exist(),

            'time' => rule()->string()->exist(),
            'script' => rule()->string()->exist(),

            'task' => rule()->string(),

            'status' => rule()->int()->exist(),
        ];
    }
}