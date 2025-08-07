<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Contract;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор подрядчика
 * 
 * @property-read null|int $address_house_id Идентификатор дома
 * 
 * @property-read bool $remove_subscriber Удалять ли абонентов
 * @property-read bool $remove_key Удалять ли ключи
 */
readonly class ContractSyncRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'address_house_id' => rule()->int()->clamp(0),

            'remove_subscriber' => [filter()->default(false), rule()->bool()],
            'remove_key' => [filter()->default(false), rule()->bool()]
        ];
    }
}