<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Block;

use Selpol\Controller\Admin\Block\BlockController;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Repository\Block\FlatBlockRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property int $flat_id
 *
 * @property int $service
 * @property int $status
 *
 * @property string|null $cause
 * @property string|null $comment
 *
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property-read HouseFlat $flat
 */
class FlatBlock extends Entity
{
    /**
     * @use RepositoryTrait<FlatBlockRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'flat_block';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return OneToOneRelationship<HouseFlat>
     */
    public function flat(): OneToOneRelationship
    {
        return $this->oneToOne(HouseFlat::class, 'house_flat_id', '$flat_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'flat_id' => rule()->id(),

            'service' => rule()->required()->in(BlockController::SERVICES_FLAT)->nonNullable(),
            'status' => rule()->required()->in([1, 2, 3])->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string(),
        ];
    }
}