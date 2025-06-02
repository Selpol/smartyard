<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Repository\Address\AddressRegionRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $address_region_id
 *
 * @property string|null $region_uuid
 * @property string|null $region_iso_code
 * @property string $region_with_type
 * @property string|null $region_type
 * @property string|null $region_type_full
 * @property string $region
 *
 * @property string|null $timezone
 * 
 * @property-read AddressArea[] $aries
 * @property-read AddressCity[] $cities
 */
class AddressRegion extends Entity
{
    /**
     * @use RepositoryTrait<AddressRegionRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'addresses_regions';

    public static string $columnId = 'address_region_id';

    public static ?array $fillable = [
        'region_uuid' => true,
        'region_iso_code' => true,
        'region_with_type' => true,
        'region_type' => true,
        'region_type_full' => true,
        'region' => true,

        'timezone' => true,
    ];

    /**
     * @return OneToManyRelationship<AddressArea>
     */
    public function aries(): OneToManyRelationship
    {
        return $this->oneToMany(AddressArea::class, 'address_region_id', 'address_region_id');
    }

    /**
     * @return OneToManyRelationship<AddressCity>
     */
    public function cities(): OneToManyRelationship
    {
        return $this->oneToMany(AddressCity::class, 'address_region_id', 'address_region_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'region_uuid' => rule()->uuid4(),
            'region_iso_code' => rule()->string(),
            'region_with_type' => rule()->required()->string()->nonNullable(),
            'region_type' => rule()->string(),
            'region_type_full' => rule()->string(),
            'region' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}