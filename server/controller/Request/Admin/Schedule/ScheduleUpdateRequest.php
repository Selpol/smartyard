<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Schedule;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор расписания
 *
 * @property-read string $title Заголовок
 *
 * @property-read string $time Время
 * @property-read string $script Скрипт
 *
 * @property-read int $status Статус
 */
readonly class ScheduleUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'title' => rule()->string()->max(256)->exist(),

            'time' => rule()->string()->exist(),
            'script' => rule()->string()->exist(),

            'status' => rule()->int()->exist(),
        ];
    }
}