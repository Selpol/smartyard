<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор подрядчика
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

            'remove_subscriber' => [filter()->default(false), rule()->bool()],
            'remove_key' => [filter()->default(false), rule()->bool()]
        ];
    }
}