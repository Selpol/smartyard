<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Address;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string|null $region_uuid
 * @property-read string|null $region_iso_code
 * @property-read string $region_with_type
 * @property-read string|null $region_type
 * @property-read string|null $region_type_full
 * @property-read string $region
 * 
 * @property-read string|null $timezone
 */
readonly class AddressRegionStoreRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'region_uuid' => rule()->uuid(),
            'region_iso_code' => rule()->string(),
            'region_with_type' => rule()->required()->string()->nonNullable(),
            'region_type' => rule()->string(),
            'region_type_full' => rule()->string(),
            'region' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}