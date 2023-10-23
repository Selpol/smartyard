<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Framework\Entity\Entity;

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
            self::$columnId => rule()->id(),

            'address_area_id' => rule()->int()->clamp(0),
            'address_city_id' => rule()->int()->clamp(0),

            'settlement_uuid' => rule()->uuid(),
            'settlement_with_type' => rule()->required()->string()->nonNullable(),
            'settlement_type' => rule()->string(),
            'settlement_type_full' => rule()->string(),
            'settlement' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}