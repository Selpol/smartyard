<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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
            self::$columnId => [Rule::id()],

            'address_region_id' => [Rule::int(), Rule::min(0), Rule::max()],
            'address_area_id' => [Rule::int(), Rule::min(0), Rule::max()],

            'city_uuid' => [Rule::uuid()],
            'city_with_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'city_type' => [Rule::length()],
            'city_type_full' => [Rule::length()],
            'city' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'timezone' => [Rule::length()]
        ];
    }
}