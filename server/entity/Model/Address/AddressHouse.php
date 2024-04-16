<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Repository\Address\AddressHouseRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
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
 */
class AddressHouse extends Entity
{
    /**
     * @use RepositoryTrait<AddressHouseRepository>
     */
    use RepositoryTrait, RelationshipTrait;

    public static string $table = 'addresses_houses';

    public static string $columnId = 'address_house_id';

    /**
     * @return OneToManyRelationship<HouseFlat>
     */
    public function getFlats(): OneToManyRelationship
    {
        return $this->oneToMany(HouseFlat::class, 'address_house_id', 'address_house_id');
    }

    /**
     * @return ManyToManyRelationship<HouseEntrance>
     */
    public function getEntrances(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseEntrance::class, 'houses_houses_entrances', 'address_house_id', 'address_house_id', 'house_entrance_id');
    }

    /**
     * @return ManyToManyRelationship<DeviceCamera>
     */
    public function getCameras(): ManyToManyRelationship
    {
        return $this->manyToMany(DeviceCamera::class, 'houses_cameras_houses', 'address_house_id', 'address_house_id', 'camera_id');
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