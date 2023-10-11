<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $address_street_id
 *
 * @property int|null $address_city_id
 * @property int|null $address_settlement_id
 *
 * @property string|null $street_uuid
 * @property string $street_with_type
 * @property string|null $street_type
 * @property string|null $street_type_full
 * @property string $street
 *
 * @property string|null $timezone
 */
class AddressStreet extends Entity
{
    public static string $table = 'addresses_streets';

    public static string $columnId = 'address_street_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'address_city_id' => [Rule::int(), Rule::min(0), Rule::max()],
            'address_settlement_id' => [Rule::int(), Rule::min(0), Rule::max()],

            'street_uuid' => [Rule::uuid()],
            'street_with_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'street_type' => [Rule::length()],
            'street_type_full' => [Rule::length()],
            'street' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'timezone' => [Rule::length()]
        ];
    }
}