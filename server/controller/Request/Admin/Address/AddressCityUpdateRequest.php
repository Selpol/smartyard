<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 * 
 * @property-read int|null $address_region_id
 * @property-read int|null $address_area_id
 * 
 * @property-read string|null $city_uuid
 * @property-read string $city_with_type
 * @property-read string|null $city_type
 * @property-read string|null $city_type_full
 * @property-read string $city
 * 
 * @property-read string|null $timezone
 */
readonly class AddressCityUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'address_region_id' => rule()->int()->clamp(0),
            'address_area_id' => rule()->int()->clamp(0),

            'city_uuid' => rule()->uuid4(),
            'city_with_type' => rule()->required()->string()->nonNullable(),
            'city_type' => rule()->string(),
            'city_type_full' => rule()->string(),
            'city' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string(),
        ];
    }
}