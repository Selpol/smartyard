<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Dvr;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Entity\Repository\Dvr\DvrRecordRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

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
 * @property int<0, 3> $state 0 = created, 1 = in progress, 2 = completed, 3 = error
 * 
 * @property-read DeviceCamera $camera
 * @property-read HouseSubscriber $subscriber
 */
class DvrRecord extends Entity
{
    /**
     * @use RepositoryTrait<DvrRecordRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'camera_records';

    public static string $columnId = 'record_id';

    /**
     * @return OneToOneRelationship<DeviceCamera>
     */
    public function camera(): OneToOneRelationship
    {
        return $this->oneToOne(DeviceCamera::class, 'camera_id', 'camera_id');
    }

    /**
     * @return OneToOneRelationship<HouseSubscriber>
     */
    public function subscriber(): OneToOneRelationship
    {
        return $this->oneToOne(HouseSubscriber::class, 'house_subscriber_id', 'subscriber_id');
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'camera_id' => rule()->id(),
            'subscriber_id' => rule()->id(),

            'start' => rule()->required()->int()->nonNullable(),
            'finish' => rule()->required()->int()->nonNullable(),

            'filename' => rule()->required()->string()->nonNullable(),

            'expire' => rule()->required()->int()->nonNullable(),

            'state' => rule()->required()->in([0, 1, 2, 3])->nonNullable()
        ];
    }
}