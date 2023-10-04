<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $house_domophone_id
 *
 * @property int $enabled
 *
 * @property string $model
 * @property string $server
 * @property string $url
 * @property string $credentials
 * @property string $dtmf
 *
 * @property int $first_time
 *
 * @property int|null $nat
 *
 * @property int $locks_are_open
 *
 * @property string|null $ip
 *
 * @property string|null $comment
 */
class Intercom extends Entity
{
    public static string $table = 'houses_domophones';

    public static string $columnId = 'house_domophone_id';

    protected static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'enabled' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'model' => [Rule::required(), Rule::in(array_keys(IntercomModel::models())), Rule::nonNullable()],
            'server' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()],
            'credentials' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'dtmf' => [Rule::required(), Rule::length(1, 1), Rule::nonNullable()],

            'first_time' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'nat' => [Rule::int()],

            'locks_are_open' => [Rule::int()],

            'ip' => [Rule::ipV4()],

            'comment' => [Rule::length()]
        ];
    }
}