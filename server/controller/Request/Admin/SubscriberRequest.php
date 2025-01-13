<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|string $name Имя
 * @property-read null|string $patronymic Отчество
 *
 * @property-read null|string $id Номер телефона
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class SubscriberRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'name' => rule()->string()->clamp(0, 64),
            'patronymic' => rule()->string()->clamp(0, 64),

            'id' => rule()->string()->clamp(11, 11)->regexp('/^7\d{10}$/'),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}