<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Inbox;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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

    protected static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'house_subscriber_id' => [Rule::id()],

            'id' => [Rule::required(), Rule::nonNullable()],

            'date' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'title' => [Rule::length()],
            'msg' => [Rule::required(), Rule::length(max: 4096), Rule::nonNullable()],

            'action' => [Rule::length()],

            'expire' => [Rule::int()],

            'push_message_id' => [Rule::length(max: 4096)],

            'delivered' => [Rule::int()],
            'readed' => [Rule::int()],

            'code' => [Rule::length()]
        ];
    }
}