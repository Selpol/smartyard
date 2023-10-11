<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $house_subscriber_id
 *
 * @property string|null $id
 * @property string|null $aud_jti
 * @property string|null $auth_token
 *
 * @property int|null $platform
 *
 * @property string|null $push_token
 * @property int|null $push_token_type
 *
 * @property string|null $voip_token
 * @property int|null $voip_enabled
 *
 * @property int|null $registered
 * @property int|null $last_seen
 *
 * @property string|null $subscriber_name
 * @property string|null $subscriber_patronymic
 */
class HouseSubscriber extends Entity
{
    public static string $table = 'houses_subscribers_mobile';

    public static string $columnId = 'house_subscriber_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => [Rule::id()],

            'id' => [Rule::length()],
            'aud_jti' => [Rule::length()],
            'auth_token' => [Rule::length()],

            'platform' => [Rule::length()],

            'push_token' => [Rule::length(16)],
            'push_token_type' => [Rule::int()],

            'voip_token' => [Rule::length()],
            'voip_enabled' => [Rule::int()],

            'registered' => [Rule::int()],
            'last_seen' => [Rule::int()],

            'subscriber_name' => [Rule::length()],
            'subscriber_patronymic' => [Rule::length()]
        ];
    }
}