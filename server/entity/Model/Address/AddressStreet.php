<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Repository\Address\AddressStreetRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $address_street_id
 *
 * @property int|null $address_city_id
 * @property int|null $address_settlement_id
 *
 * @property string|null $street_uuid
 * @property string $street_with_type
 * @property string|null $street_type
 * @property string|null $street_type_full
 * @property string $street
 *
 * @property string|null $timezone
 * 
 * @property-read AddressCity|null $city
 * @property-read AddressSettlement|null $settlement
 * 
 * @property-read AddressHouse[] $houses
 */
class AddressStreet extends Entity
{
    /**
     * @use RepositoryTrait<AddressStreetRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'addresses_streets';

    public static string $columnId = 'address_street_id';

    public static ?array $fillable = [
        'address_city_id' => true,
        'address_settlement_id' => true,

        'street_uuid' => true,
        'street_with_type' => true,
        'street_type' => true,
        'street_type_full' => true,
        'street' => true,

        'timezone' => true,
    ];

    /**
     * @return OneToOneRelationship<AddressCity>
     */
    public function city(): OneToOneRelationship
    {
        return $this->oneToOne(AddressCity::class, 'address_city_id', 'address_city_id');
    }

    public function settlement(): OneToOneRelationship
    {
        return $this->oneToOne(AddressSettlement::class, 'address_settlement_id', 'address_settlement_id');
    }

    /**
     * @return OneToManyRelationship<AddressHouse>
     */
    public function houses(): OneToManyRelationship
    {
        return $this->oneToMany(AddressHouse::class, 'address_street_id', 'address_street_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_city_id' => rule()->int()->clamp(0),
            'address_settlement_id' => rule()->int()->clamp(0),

            'street_uuid' => rule()->uuid(),
            'street_with_type' => rule()->required()->string()->nonNullable(),
            'street_type' => rule()->string(),
            'street_type_full' => rule()->string(),
            'street' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}