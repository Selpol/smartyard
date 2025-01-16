<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $oid Идентификатор группы
 * 
 * @property-read string $name Название
 * 
 * @property-read string $type Тип абонент, камера, домофон, ключ, адрес
 * 
 * @property-read string $for Сущность подрядчик или адрес
 * @property-read string $id Идентификатор сущности
 * 
 * @property-read mixed $value Значение
 */
readonly class GroupUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'name' => rule()->required()->string()->nonNullable(),
            'type' => rule()->required()->in(['subscriber', 'camera', 'intercom', 'key', 'address'])->nonNullable(),

            'for' => rule()->required()->in(['contractor', 'address'])->nonNullable(),
            'id' => rule()->required()->string()->clamp(0, 64)->nonNullable(),

            'value' => rule()->required()->nonNullable()
        ];
    }
}