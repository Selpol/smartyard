<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int|null $address_area_id
 * @property-read int|null $address_city_id
 * 
 * @property-read string|null $settlement_uuid
 * @property-read string $settlement_with_type
 * @property-read string|null $settlement_type
 * @property-read string|null $settlement_type_full
 * @property-read string $settlement
 * 
 * @property-read string|null $timezone
 */
readonly class AddressSettlementStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'address_area_id' => rule()->int()->clamp(0),
            'address_city_id' => rule()->int()->clamp(0),

            'settlement_uuid' => rule()->uuid(),
            'settlement_with_type' => rule()->required()->string()->nonNullable(),
            'settlement_type' => rule()->string(),
            'settlement_type_full' => rule()->string(),
            'settlement' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}