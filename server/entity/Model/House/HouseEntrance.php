<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $house_entrance_id
 *
 * @property string|null $entrance_type
 * @property string $entrance
 *
 * @property double $lat
 * @property double $lon
 *
 * @property int $shared
 * @property int $plog
 *
 * @property string|null $caller_id
 *
 * @property int|null $camera_id
 *
 * @property int $house_domophone_id
 *
 * @property int|null $domophone_output
 *
 * @property string|null $cms
 * @property int|null $cms_type
 * @property string|null $cms_levels
 *
 * @property int $locks_disabled
 */
class HouseEntrance extends Entity
{
    public static string $table = 'houses_entrances';

    public static string $columnId = 'house_entrance_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'entrance_type' => [Rule::length()],
            'entrance' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'lat' => [Rule::float()],
            'lon' => [Rule::float()],

            'shared' => [Rule::int()],
            'plog' => [Rule::int()],

            'caller_id' => [Rule::length()],

            'camera_id' => [Rule::int(), Rule::min(0), Rule::max()],

            'house_domophone_id' => [Rule::id()],

            'domophone_output' => [Rule::int()],

            'cms' => [Rule::length()],
            'cms_type' => [Rule::int()],
            'cms_levels' => [Rule::length()],

            'locks_disabled' => [Rule::int()]
        ];
    }
}