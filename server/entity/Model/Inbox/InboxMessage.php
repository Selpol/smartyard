<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Inbox;

use Selpol\Entity\Entity;

/**
 * @property int $msg_id
 *
 * @property int $house_subscriber_id
 *
 * @property int $date
 */
class InboxMessage extends Entity
{
    public static string $table = 'inbox';

    public static string $columnId = 'msg_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'house_subscriber_id' => rule()->id(),

            'id' => rule()->required()->nonNullable(),

            'date' => rule()->required()->int()->nonNullable(),

            'title' => rule()->string(),
            'msg' => rule()->required()->string()->max(4096)->nonNullable(),

            'action' => rule()->string(),

            'expire' => rule()->int(),

            'push_message_id' => rule()->string()->max(4096),

            'delivered' => rule()->int(),
            'readed' => rule()->int(),

            'code' => rule()->string()
        ];
    }
}