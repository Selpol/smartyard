<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Entity\Model\Inbox\InboxMessage;
use Selpol\Entity\Repository\House\HouseSubscriberRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
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
 * @property-read InboxMessage[] $messages
 * @property-read HouseFlat[] $flats
 * @property-read DvrRecord[] $records
 * 
 * @property-read SubscriberBlock[] $blocks
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

    /**
     * @return OneToManyRelationship<InboxMessage>
     */
    public function messages(): OneToManyRelationship
    {
        return $this->oneToMany(InboxMessage::class, 'house_subscriber_id', 'house_subscriber_id');
    }

    /**
     * @return ManyToManyRelationship<HouseFlat>
     */
    public function flats(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseFlat::class, 'houses_flats_subscribers', localRelation: 'house_subscriber_id', foreignRelation: 'house_flat_id');
    }

    /**
     * @return OneToManyRelationship<DvrRecord>
     */
    public function records(): OneToManyRelationship
    {
        return $this->oneToMany(DvrRecord::class, 'subscriber_id', 'house_subscriber_id');
    }

    /**
     * @return OneToManyRelationship<SubscriberBlock>
     */
    public function blocks(): OneToManyRelationship
    {
        return $this->oneToMany(SubscriberBlock::class, 'subscriber_id', 'house_subscriber_id');
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