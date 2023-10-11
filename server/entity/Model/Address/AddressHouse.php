<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $address_house_id
 *
 * @property int|null $address_settlement_id
 * @property int|null $address_street_id
 *
 * @property string|null $house_uuid
 * @property string|null $house_type
 * @property string|null $house_type_full
 * @property string $house_full
 * @property string $house
 *
 * @property string|null $timezone
 */
class AddressHouse extends Entity
{
    public static string $table = 'addresses_houses';

    public static string $columnId = 'address_house_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'address_settlement_id' => [Rule::int(), Rule::min(0), Rule::max()],
            'address_street_id' => [Rule::int(), Rule::min(0), Rule::max()],

            'house_uuid' => [Rule::uuid()],
            'house_type' => [Rule::length()],
            'house_type_full' => [Rule::length()],
            'house_full' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'house' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'timezone' => [Rule::length()]
        ];
    }
}