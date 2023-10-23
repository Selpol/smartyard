<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Framework\Entity\Entity;

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
            static::$columnId => rule()->id(),

            'rfid' => rule()->required()->string()->nonNullable(),

            'access_type' => rule()->required()->in([0, 1, 2, 3, 4])->nonNullable(),
            'access_to' => rule()->id(),

            'last_seen' => rule()->int(),

            'comments' => rule()->string()
        ];
    }
}