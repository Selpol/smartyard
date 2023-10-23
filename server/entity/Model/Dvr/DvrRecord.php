<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Dvr;

use Selpol\Framework\Entity\Entity;

/**
 * @property int $record_id
 * @property int $camera_id
 * @property int $subscriber_id
 *
 * @property int $start
 * @property int $finish
 *
 * @property string $filename
 *
 * @property int $expire
 *
 * @property int $state
 */
class DvrRecord extends Entity
{
    public static string $table = 'camera_records';

    public static string $columnId = 'record_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'camera_id' => rule()->id(),
            'subscriber_id' => rule()->id(),

            'start' => rule()->required()->int()->nonNullable(),
            'finish' => rule()->required()->int()->nonNullable(),

            'filename' => rule()->required()->string()->nonNullable(),

            'expire' => rule()->required()->int()->nonNullable(),

            'state' => rule()->required()->in([0, 1, 2, 3])->nonNullable()
        ];
    }
}