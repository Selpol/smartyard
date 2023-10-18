<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Entity;

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
 *
 * @property int|null $admin_block
 * @property string|null $description_block
 */
class HouseSubscriber extends Entity
{
    public static string $table = 'houses_subscribers_mobile';

    public static string $columnId = 'house_subscriber_id';

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'id' => rule()->string(),
            'aud_jti' => rule()->string(),
            'auth_token' => rule()->string(),

            'platform' => rule()->string(),

            'push_token' => rule()->string()->min(16),
            'push_token_type' => rule()->int(),

            'voip_token' => rule()->string(),
            'voip_enabled' => rule()->int(),

            'registered' => rule()->int(),
            'last_seen' => rule()->int(),

            'subscriber_name' => rule()->string(),
            'subscriber_patronymic' => rule()->string(),

            'admin_block' => rule()->int(),
            'description_block' => rule()->string()
        ];
    }
}