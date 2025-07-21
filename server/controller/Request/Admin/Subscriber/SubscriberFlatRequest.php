<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Subscriber;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $house_subscriber_id Идентификатор абонента
 * @property-read int $flat_id Идентификатор квартиры
 * @property-read int $role Роль абонента в квартире, 0 - Владелец, 1 - Жилец
 * @property-read int $call Статус звонков, 0 - Выключены, 1 - Включено
 */
readonly class SubscriberFlatRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'house_subscriber_id' => rule()->id(),
            'flat_id' => rule()->id(),
            'role' => [filter()->default(1), rule()->int()->clamp(0, 1)->exist()],
            'call' => [filter()->default(1), rule()->int()->clamp(0, 1)->exist()],
        ];
    }
}