<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;

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
            self::$columnId => rule()->id(),

            'address_settlement_id' => rule()->int()->clamp(0),
            'address_street_id' => rule()->int()->clamp(0),

            'house_uuid' => rule()->uuid(),
            'house_type' => rule()->required()->string()->nonNullable(),
            'house_type_full' => rule()->string(),
            'house_full' => rule()->required()->string()->nonNullable(),
            'house' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}