<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $address_area_id
 *
 * @property int $address_region_id
 *
 * @property string|null $area_uuid
 * @property string $area_with_type
 * @property string|null $area_type
 * @property string|null $area_type_full
 * @property string $area
 *
 * @property string|null $timezone
 */
class AddressArea extends Entity
{
    public static string $table = 'addresses_areas';

    public static string $columnId = 'address_area_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'address_region_id' => [Rule::id()],

            'area_uuid' => [Rule::uuid()],
            'area_with_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'area_type' => [Rule::length()],
            'area_type_full' => [Rule::length()],
            'area' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'timezone' => [Rule::length()]
        ];
    }
}