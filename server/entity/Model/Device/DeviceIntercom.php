<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Entity;

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
class DeviceIntercom extends Entity
{
    public static string $table = 'houses_domophones';

    public static string $columnId = 'house_domophone_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'enabled' => rule()->required()->int()->nonNullable(),

            'model' => rule()->required()->in(array_keys(IntercomModel::models()))->nonNullable(),
            'server' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'credentials' => rule()->required()->string()->nonNullable(),
            'dtmf' => rule()->required()->string()->clamp(1, 1)->nonNullable(),

            'first_time' => rule()->required()->int()->nonNullable(),

            'nat' => rule()->int(),

            'locks_are_open' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string()
        ];
    }
}