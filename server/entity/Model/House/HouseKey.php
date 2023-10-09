<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $house_rfid_id
 *
 * @property string $rfid
 *
 * @property int $access_type
 * @property int $access_to
 *
 * @property int|null $last_seen
 *
 * @property string|null $comments
 */
class HouseKey extends Entity
{
    public static string $table = 'houses_rfids';

    public static string $columnId = 'house_rfid_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'rfid' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'access_type' => [Rule::required(), Rule::in([0, 1, 2, 3, 4]), Rule::nonNullable()],
            'access_to' => [Rule::id()],

            'last_seen' => [Rule::int()],

            'comments' => [Rule::length()]
        ];
    }
}