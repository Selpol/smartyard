<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Block;

use Selpol\Entity\Repository\Block\SubscriberBlockRepository;
use Selpol\Framework\Entity\Entity;
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
 */
class SubscriberBlock extends Entity
{
    /**
     * @use RepositoryTrait<SubscriberBlockRepository>
     */
    use RepositoryTrait;

    public static string $table = 'subscriber_block';

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'subscriber_id' => rule()->id(),

            'service' => rule()->required()->in([0, 1, 2, 3, 4, 5, 6, 7])->nonNullable(),
            'status' => rule()->required()->in([1, 2, 3])->nonNullable(),

            'cause' => rule()->string(),
            'comment' => rule()->string(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string(),
        ];
    }
}