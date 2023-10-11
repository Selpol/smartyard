<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;

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
            self::$columnId => rule()->id(),

            'address_region_id' => rule()->id(),

            'area_uuid' => rule()->uuid(),
            'area_with_type' => rule()->required()->string()->nonNullable(),
            'area_type' => rule()->string(),
            'area_type_full' => rule()->string(),
            'area' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string(),
        ];
    }
}