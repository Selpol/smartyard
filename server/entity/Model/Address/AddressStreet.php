<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Entity;

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
            self::$columnId => rule()->id(),

            'address_city_id' => rule()->int()->clamp(0),
            'address_settlement_id' => rule()->int()->clamp(0),

            'street_uuid' => rule()->uuid(),
            'street_with_type' => rule()->required()->string()->nonNullable(),
            'street_type' => rule()->string(),
            'street_type_full' => rule()->string(),
            'street' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}