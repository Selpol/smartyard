<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository\House\HouseGsmRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 * 
 * @property int $house_subscriber_id
 * @property int $house_domophone_id
 * 
 * @property int $count Сколько раз был добавлен на GSM
 *
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property-read HouseSubscriber $subscriber
 * @property-read \Selpol\Entity\Model\Device\DeviceIntercom $intercom
 */
class HouseGsm extends Entity
{
    /**
     * @use RepositoryTrait<HouseGsmRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_gsm_subscribers';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return OneToOneRelationship<HouseSubscriber>
     */
    public function subscriber(): OneToOneRelationship
    {
        return $this->oneToOne(HouseSubscriber::class, 'house_subscriber_id', 'house_subscriber_id');
    }

    /**
     * @return OneToOneRelationship<DeviceIntercom>
     */
    public function intercom(): OneToOneRelationship
    {
        return $this->oneToOne(DeviceIntercom::class, 'house_domophone_id', 'house_domophone_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'house_subscriber_id' => rule()->id(),
            'house_domophone_id' => rule()->id(),

            'count' => rule()->int()->min(0)->exist(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}