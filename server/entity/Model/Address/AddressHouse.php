<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository\Address\AddressHouseRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $address_house_id
 *
 * @property int|null $address_settlement_id
 * @property int|null $address_street_id
 *
 * @property string|null $house_uuid
 * @property string|null $house_type
 * @property string|null $house_type_full
 * @property string $house_full
 * @property string $house
 *
 * @property string|null $timezone
 * 
 * @property-read AddressSettlement|null $settlement
 * @property-read AddressStreet|null $street
 * 
 * @property-read DeviceCamera[] $cameras
 */
class AddressHouse extends Entity
{
    /**
     * @use RepositoryTrait<AddressHouseRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'addresses_houses';

    public static string $columnId = 'address_house_id';

    /**
     * @return OneToOneRelationship<AddressSettlement>
     */
    public function settlement(): OneToOneRelationship
    {
        return $this->oneToOne(AddressSettlement::class, 'address_settlement_id', 'address_settlement_id');
    }

    /**
     * @return OneToOneRelationship<AddressStreet>
     */
    public function street(): OneToOneRelationship
    {
        return $this->oneToOne(AddressStreet::class, 'address_street_id', 'address_street_id');
    }

    /**
     * @return ManyToManyRelationship<DeviceCamera>
     */
    public function cameras(): ManyToManyRelationship
    {
        return $this->manyToMany(DeviceCamera::class, 'houses_cameras_houses', localRelation: 'address_house_id', foreignRelation: 'camera_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_settlement_id' => rule()->int()->clamp(0),
            'address_street_id' => rule()->int()->clamp(0),

            'house_uuid' => rule()->uuid(),
            'house_type' => rule()->required()->string()->nonNullable(),
            'house_type_full' => rule()->string(),
            'house_full' => rule()->required()->string()->nonNullable(),
            'house' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}