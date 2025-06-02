<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 * 
 * @property-read int|null $address_city_id
 * @property-read int|null $address_settlement_id
 * 
 * @property-read string|null $street_uuid
 * @property-read string $street_with_type
 * @property-read string|null $street_type
 * @property-read string|null $street_type_full
 * @property-read string $street
 * 
 * @property-read string|null $timezone
 */
readonly class AddressStreetUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'address_city_id' => rule()->int()->clamp(0),
            'address_settlement_id' => rule()->int()->clamp(0),

            'street_uuid' => rule()->uuid4(),
            'street_with_type' => rule()->required()->string()->nonNullable(),
            'street_type' => rule()->string(),
            'street_type_full' => rule()->string(),
            'street' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}