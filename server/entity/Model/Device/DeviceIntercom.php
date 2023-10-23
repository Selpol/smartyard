<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Framework\Entity\Entity;

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
 *
 * @property string|null $sos_number
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
            'dtmf' => rule()->required()->string()->in(["*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"])->nonNullable(),

            'first_time' => rule()->required()->int()->nonNullable(),

            'nat' => rule()->int(),

            'locks_are_open' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'sos_number' => rule()->string()
        ];
    }
}