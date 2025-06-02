<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Repository\Address\AddressSettlementRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $address_settlement_id
 *
 * @property int|null $address_area_id
 * @property int|null $address_city_id
 *
 * @property string|null $settlement_uuid
 * @property string $settlement_with_type
 * @property string|null $settlement_type
 * @property string|null $settlement_type_full
 * @property string $settlement
 *
 * @property string|null $timezone
 * 
 * @property-read AddressArea|null $area
 * @property-read AddressCity|null $city
 * 
 * @property-read AddressStreet[] $streets
 * @property-read AddressHouse[] $houses
 */
class AddressSettlement extends Entity
{
    /**
     * @use RepositoryTrait<AddressSettlementRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'addresses_settlements';

    public static string $columnId = 'address_settlement_id';

    public static ?array $fillable = [
        'address_area_id' => true,
        'address_city_id' => true,

        'settlement_uuid' => true,
        'settlement_with_type' => true,
        'settlement_type' => true,
        'settlement_type_full' => true,
        'settlement' => true,

        'timezone' => true
    ];

    /**
     * @return OneToOneRelationship<AddressArea>
     */
    public function area(): OneToOneRelationship
    {
        return $this->oneToOne(AddressArea::class, 'address_area_id', 'address_area_id');
    }

    /**
     * @return OneToOneRelationship<AddressCity>
     */
    public function city(): OneToOneRelationship
    {
        return $this->oneToOne(AddressCity::class, 'address_city_id', 'address_city_id');
    }

    /**
     * @return OneToManyRelationship<AddressStreet>
     */
    public function streets(): OneToManyRelationship
    {
        return $this->oneToMany(AddressStreet::class, 'address_settlement_id', 'address_settlement_id');
    }

    /**
     * @return OneToManyRelationship<AddressHouse>
     */
    public function houses(): OneToManyRelationship
    {
        return $this->oneToMany(AddressHouse::class, 'address_settlement_id', 'address_settlement_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_area_id' => rule()->int()->clamp(0),
            'address_city_id' => rule()->int()->clamp(0),

            'settlement_uuid' => rule()->uuid4(),
            'settlement_with_type' => rule()->required()->string()->nonNullable(),
            'settlement_type' => rule()->string(),
            'settlement_type_full' => rule()->string(),
            'settlement' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}