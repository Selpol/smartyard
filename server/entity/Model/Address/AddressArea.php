<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Repository\Address\AddressAreaRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $address_area_id
 *
 * @property int $address_region_id
 *
 * @property string|null $area_uuid
 * @property string $area_with_type
 * @property string|null $area_type
 * @property string|null $area_type_full
 * @property string $area
 *
 * @property string|null $timezone
 * 
 * @property-read AddressRegion $region
 * 
 * @property-read AddressCity[] $cities
 * @property-read AddressSettlement[] $settlements
 */
class AddressArea extends Entity
{
    /**
     * @use RepositoryTrait<AddressAreaRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'addresses_areas';

    public static string $columnId = 'address_area_id';

    /**
     * @return OneToOneRelationship<AddressRegion>
     */
    public function region(): OneToOneRelationship
    {
        return $this->oneToOne(AddressRegion::class, 'address_region_id', 'address_region_id');
    }

    /**
     * @return OneToManyRelationship<AddressCity>
     */
    public function cities(): OneToManyRelationship
    {
        return $this->oneToMany(AddressCity::class, 'address_area_id', 'address_area_id');
    }

    /**
     * @return OneToManyRelationship<AddressSettlement>
     */
    public function settlements(): OneToManyRelationship
    {
        return $this->oneToMany(AddressSettlement::class, 'address_area_id', 'address_area_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_region_id' => rule()->id(),

            'area_uuid' => rule()->uuid(),
            'area_with_type' => rule()->required()->string()->nonNullable(),
            'area_type' => rule()->string(),
            'area_type_full' => rule()->string(),
            'area' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string(),
        ];
    }
}