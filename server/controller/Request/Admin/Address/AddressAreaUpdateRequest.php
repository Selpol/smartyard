<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 * 
 * @property-read int $address_region_id
 * 
 * @property-read string|null $area_uuid
 * @property-read string $area_with_type
 * @property-read string|null $area_type
 * @property-read string|null $area_type_full
 * @property-read string $area
 * 
 * @property-read string|null $timezone
 */
readonly class AddressAreaUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'address_region_id' => rule()->id(),

            'area_uuid' => rule()->uuid4(),
            'area_with_type' => rule()->required()->string()->nonNullable(),
            'area_type' => rule()->string(),
            'area_type_full' => rule()->string(),
            'area' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string(),
        ];
    }
}