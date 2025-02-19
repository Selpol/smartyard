<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Entrance;

use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Repository\Entrance\EntranceCmsRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $house_entrance_id Идентификатор входа
 * 
 * @property string $cms КМС
 * @property int $dozen 
 * @property string $unit
 * @property int $apartment Квартира
 * 
 * @property-read HouseEntrance $entrance
 */
class EntranceCms extends Entity
{
    /**
     * @use RepositoryTrait<EntranceCmsRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_entrances_cmses';

    public static string $columnId = 'house_entrance_id';

    public static string $columnIdStrategy = self::STRATEGY_NONE;

    /**
     * @return OneToOneRelationship<HouseEntrance>
     */
    public function entrance(): OneToOneRelationship
    {
        return $this->oneToOne(HouseEntrance::class, 'house_entrance_id', 'house_entrance_id');
    }

    public static function getColumns(): array
    {
        return [
            'house_entrance_id' => rule()->id(),

            'cms' => rule()->string()->exist(),
            'dozen' => rule()->int()->clamp(0)->exist(),
            'unit' => rule()->string()->exist(),
            'apartment' => rule()->int()->clamp(0)->exist(),
        ];
    }
}