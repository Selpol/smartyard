<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Dvr;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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

    protected static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'camera_id' => [Rule::id()],
            'subscriber_id' => [Rule::id()],

            'start' => [Rule::required(), Rule::int(), Rule::nonNullable()],
            'finish' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'filename' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'expire' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'state' => [Rule::required(), Rule::in([0, 1, 2, 3]), Rule::nonNullable()]
        ];
    }
}