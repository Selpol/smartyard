<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $address_settlement_id
 *
 * @property int|null $address_area_id
 * @property int|null $address_city_id
 *
 * @property string|null $settlement_uuid
 * @property string $settlement_with_type
 * @property string|null $settlement_type
 * @property string|null $settlement_type_full
 * @property string $settlement
 *
 * @property string|null $timezone
 */
class AddressSettlement extends Entity
{
    public static string $table = 'addresses_settlements';

    public static string $columnId = 'address_settlement_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'address_area_id' => [Rule::int(), Rule::min(0), Rule::max()],
            'address_city_id' => [Rule::int(), Rule::min(0), Rule::max()],

            'settlement_uuid' => [Rule::uuid()],
            'settlement_with_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'settlement_type' => [Rule::length()],
            'settlement_type_full' => [Rule::length()],
            'settlement' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'timezone' => [Rule::length()]
        ];
    }
}