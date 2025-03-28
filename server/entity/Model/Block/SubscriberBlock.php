<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Block;

use Selpol\Controller\Admin\Block\BlockController;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Entity\Repository\Block\SubscriberBlockRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property int $subscriber_id
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
 * @property-read HouseSubscriber $subscriber
 */
class SubscriberBlock extends Entity
{
    /**
     * @use RepositoryTrait<SubscriberBlockRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'subscriber_block';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return OneToOneRelationship<HouseSubscriber>
     */
    public function subscriber(): OneToOneRelationship
    {
        return $this->oneToOne(HouseSubscriber::class, 'house_subscriber_id', 'subscriber_id');
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'subscriber_id' => rule()->id(),

            'service' => rule()->required()->in(BlockController::SERVICES_SUBSCRIBER)->nonNullable(),
            'status' => rule()->required()->in([1, 2, 3])->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string(),
        ];
    }
}