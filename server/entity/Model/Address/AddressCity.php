<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;

/**
 * @property int $address_city_id
 *
 * @property int|null $address_region_id
 * @property int|null $address_area_id
 *
 * @property string|null $city_uuid
 * @property string $city_with_type
 * @property string|null $city_type
 * @property string|null $city_type_full
 * @property string $city
 *
 * @property string|null $timezone
 */
class AddressCity extends Entity
{
    public static string $table = 'addresses_cities';

    public static string $columnId = 'address_city_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_region_id' => rule()->int()->clamp(0),
            'address_area_id' => rule()->int()->clamp(0),

            'city_uuid' => rule()->uuid(),
            'city_with_type' => rule()->required()->string()->nonNullable(),
            'city_type' => rule()->string(),
            'city_type_full' => rule()->string(),
            'city' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string(),
        ];
    }
}