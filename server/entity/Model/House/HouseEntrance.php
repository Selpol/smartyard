<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Entrance\EntranceCms;
use Selpol\Entity\Repository\House\HouseEntranceRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $house_entrance_id
 *
 * @property string|null $entrance_type
 * @property string $entrance
 *
 * @property double $lat
 * @property double $lon
 *
 * @property int $shared
 * @property int $plog
 *
 * @property string|null $caller_id
 *
 * @property int|null $camera_id
 *
 * @property int $house_domophone_id
 *
 * @property int|null $domophone_output
 *
 * @property string|null $cms
 * @property int|null $cms_type
 * @property string|null $cms_levels
 *
 * @property int $locks_disabled
 *
 * @property-read DeviceCamera $camera
 * @property-read DeviceIntercom $intercom
 * 
 * @property-read AddressHouse[] $houses
 * @property-read HouseFlat[] $flats
 * 
 * @property-read EntranceCms[] $cmses
 */
class HouseEntrance extends Entity
{
    /**
     * @use RepositoryTrait<HouseEntranceRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_entrances';

    public static string $columnId = 'house_entrance_id';

    /**
     * @return OneToOneRelationship<DeviceCamera>
     */
    public function camera(): OneToOneRelationship
    {
        return $this->oneToOne(DeviceCamera::class, 'camera_id', 'camera_id');
    }

    /**
     * @return OneToOneRelationship<DeviceIntercom>
     */
    public function intercom(): OneToOneRelationship
    {
        return $this->oneToOne(DeviceIntercom::class, 'house_domophone_id', 'house_domophone_id');
    }

    /**
     * @return ManyToManyRelationship<AddressHouse>
     */
    public function houses(): ManyToManyRelationship
    {
        return $this->manyToMany(AddressHouse::class, 'houses_houses_entrances', localRelation: 'house_entrance_id', foreignRelation: 'address_house_id');
    }

    /**
     * @return ManyToManyRelationship<HouseFlat>
     */
    public function flats(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseFlat::class, 'houses_entrances_flats', localRelation: 'house_entrance_id', foreignRelation: 'house_flat_id');
    }

    /**
     * @return OneToManyRelationship<EntranceCms>
     */
    public function cmses(): OneToManyRelationship
    {
        return $this->oneToMany(EntranceCms::class, 'house_entrance_id', 'house_entrance_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'entrance_type' => rule()->string(),
            'entrance' => rule()->required()->string()->nonNullable(),

            'lat' => rule()->float(),
            'lon' => rule()->float(),

            'shared' => rule()->int(),
            'plog' => rule()->int(),

            'caller_id' => rule()->string(),

            'camera_id' => rule()->int()->clamp(0),

            'house_domophone_id' => rule()->id(),

            'domophone_output' => rule()->int(),

            'cms' => rule()->string(),
            'cms_type' => rule()->int(),
            'cms_levels' => rule()->string(),

            'locks_disabled' => rule()->int(),
        ];
    }
}