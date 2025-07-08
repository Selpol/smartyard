<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Entrance;

use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Repository\Entrance\EntranceFlatRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $house_entrance_id Идентификатор входа
 * @property int $house_flat_id Идентификатор квартиры
 *
 * @property int $apartment Квартира
 * @property string $cms_levels Уровни
 *
 * @property-read HouseEntrance $entrance
 */
class EntranceFlat extends Entity
{
    /**
     * @use RepositoryTrait<EntranceFlatRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_entrances_flats';

    public static string $columnId = 'house_entrance_id';

    public static string $columnIdStrategy = self::STRATEGY_NONE;

    /**
     * @return OneToOneRelationship<HouseEntrance>
     */
    public function entrance(): OneToOneRelationship
    {
        return $this->oneToOne(HouseEntrance::class, 'house_entrance_id', 'house_entrance_id');
    }

    /**
     * @return OneToOneRelationship<HouseFlat>
     */
    public function flat(): OneToOneRelationship
    {
        return $this->oneToOne(HouseFlat::class, 'house_flat_id', 'house_flat_id');
    }

    public static function getColumns(): array
    {
        return [
            'house_entrance_id' => rule()->id(),
            'house_flat_id' => rule()->id(),

            'apartment' => rule()->int()->clamp(0)->exist(),
            'cms_levels' => rule()->string()->exist(),
        ];
    }
}