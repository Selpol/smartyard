<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string|null $name Название
 * 
 * @property-read string|null $type Тип абонент, камера, домофон, ключ, адрес
 * 
 * @property-read string|null $for Сущность подрядчик или адрес
 * @property-read string|null $id Идентификатор сущности
 * 
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class GroupIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'name' => rule()->string(),
            'type' => rule()->in(['subscriber', 'camera', 'intercom', 'key', 'address']),

            'for' => rule()->in(['contractor', 'address']),
            'id' => rule()->string()->clamp(0, 64),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}