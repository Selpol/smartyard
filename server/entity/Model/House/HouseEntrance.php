<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Repository\House\HouseEntranceRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
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
 * @property-read HouseFlat[] $flats
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
     * @return ManyToManyRelationship<HouseFlat>
     */
    public function flats(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseEntrance::$entrance, 'houses_entrances_flats', localRelation: 'house_entrance_id', foreignRelation: 'house_flat_id');
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