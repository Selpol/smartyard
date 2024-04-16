<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Address;

use Selpol\Entity\Repository\Address\AddressSettlementRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
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
 */
class AddressSettlement extends Entity
{
    /**
     * @use RepositoryTrait<AddressSettlementRepository>
     */
    use RepositoryTrait, RelationshipTrait;

    public static string $table = 'addresses_settlements';

    public static string $columnId = 'address_settlement_id';

    /**
     * @return OneToManyRelationship<AddressStreet>
     */
    public function getStreets(): OneToManyRelationship
    {
        return $this->oneToMany(AddressStreet::class, 'address_settlement_id');
    }

    /**
     * @return OneToManyRelationship<AddressHouse>
     */
    public function getHouses(): OneToManyRelationship
    {
        return $this->oneToMany(AddressHouse::class, 'address_settlement_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_area_id' => rule()->int()->clamp(0),
            'address_city_id' => rule()->int()->clamp(0),

            'settlement_uuid' => rule()->uuid(),
            'settlement_with_type' => rule()->required()->string()->nonNullable(),
            'settlement_type' => rule()->string(),
            'settlement_type_full' => rule()->string(),
            'settlement' => rule()->required()->string()->nonNullable(),

            'timezone' => rule()->string()
        ];
    }
}