<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string|null $title Заголовок задачи
 * @property-read string|null $message Сообщение завершения задачи
 *
 * @property-read class-string|null $class Обработчик задачи
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class TaskSearchRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'title' => rule()->string(),
            'message' => rule()->string(),

            'class' => rule()->string(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}