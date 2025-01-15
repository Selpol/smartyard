<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

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
 * @property int $role
 * 
 * @property-read DeviceCamera[] $cameras
 */
class HouseSubscriber extends Entity
{
    /**
     * @use RepositoryTrait<HouseSubscriberRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_subscribers_mobile';

    public static string $columnId = 'house_subscriber_id';

    /**
     * @return ManyToManyRelationship<DeviceCamera[]>
     */
    public function cameras(): ManyToManyRelationship
    {
        return $this->manyToMany(DeviceCamera::class, 'houses_cameras_subscribers', localRelation: 'house_subscriber_id', foreignRelation: 'camera_id');
    }

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

            'role' => rule()->int()
        ];
    }
}