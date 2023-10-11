<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $address_region_id
 *
 * @property string|null $region_uuid
 * @property string|null $region_iso_code
 * @property string $region_with_type
 * @property string|null $region_type
 * @property string|null $region_type_full
 * @property string $region
 *
 * @property string|null $timezone
 */
class AddressRegion extends Entity
{
    public static string $table = 'addresses_regions';

    public static string $columnId = 'address_region_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'region_uuid' => [Rule::uuid()],
            'region_iso_code' => [Rule::length()],
            'region_with_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'region_type' => [Rule::length()],
            'region_type_full' => [Rule::length()],
            'region' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'timezone' => [Rule::length()]
        ];
    }
}