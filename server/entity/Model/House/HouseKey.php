<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Repository\House\HouseKeyRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;
use Selpol\Framework\Kernel\Exception\KernelException;

/**
 * @property int $house_rfid_id
 *
 * @property string $rfid
 *
 * @property int $access_type
 * @property int $access_to
 *
 * @property int|null $last_seen
 *
 * @property string|null $comments
 *
 * @property-read HouseFlat $flat
 */
class HouseKey extends Entity
{
    /**
     * @use RepositoryTrait<HouseKeyRepository>
     */
    use RepositoryTrait, RelationshipTrait;

    public static string $table = 'houses_rfids';

    public static string $columnId = 'house_rfid_id';

    /**
     * @return OneToOneRelationship<HouseFlat>
     */
    public function flat(): OneToOneRelationship
    {
        if ($this->access_type !== 2)
            throw new KernelException('Не верный тип ключа для квартиры');

        return $this->oneToOne(HouseFlat::class, 'house_flat_id', 'access_to');
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'rfid' => rule()->required()->string()->nonNullable(),

            'access_type' => rule()->required()->in([0, 1, 2, 3, 4])->nonNullable(),
            'access_to' => rule()->id(),

            'last_seen' => rule()->int(),

            'comments' => rule()->string()
        ];
    }
}