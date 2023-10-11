<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $house_flat_id
 * @property int $address_house_id
 *
 * @property int|null $floor
 *
 * @property int $flat
 *
 * @property string|null $code
 *
 * @property int|null $plog
 *
 * @property int|null $manual_block
 * @property int|null $auto_block
 * @property int|null $admin_block
 *
 * @property string|null $open_code
 *
 * @property int|null $auto_open
 *
 * @property int|null $white_rabbit
 *
 * @property int|null $sip_enabled
 * @property string|null $sip_password
 *
 * @property int|null $last_opened
 * @property int|null $cms_enabled
 */
class HouseFlat extends Entity
{
    public static string $table = 'houses_flats';

    public static string $columnId = 'house_flat_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'address_house_id' => [Rule::id()],

            'floor' => [Rule::int()],

            'flat' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],

            'code' => [Rule::length(min: 5, max: 5)],

            'plog' => [Rule::int()],

            'manual_block' => [Rule::int()],
            'auto_block' => [Rule::int()],
            'admin_block' => [Rule::int()],

            'open_code' => [Rule::length()],

            'auto_open' => [Rule::int()],

            'white_rabbit' => [Rule::int()],

            'sip_enabled' => [Rule::int()],
            'sip_password' => [Rule::length()],

            'last_opened' => [Rule::int()],
            'cms_enabled' => [Rule::int()]
        ];
    }
}