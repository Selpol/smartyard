<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository\House\HouseFlatRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Relationship\OneToOneRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $house_flat_id
 * @property int $address_house_id
 *
 * @property int|null $floor
 *
 * @property string|int $flat
 *
 * @property string|null $code Код для QR
 *
 * @property int|null $plog
 *
 * @property string|null $open_code Код для открытия двери
 *
 * @property int|null $auto_open
 *
 * @property int|null $white_rabbit
 *
 * @property int|null $sip_enabled
 * @property string|null $sip_password
 *
 * @property int|null $last_opened
 * @property int|null $cms_enabled
 * 
 * @property int|null $open_code_enabled
 *
 * @property string|null $comment Комментарий
 *
 * @property-read AddressHouse $house Дом
 * @property-read HouseEntrance[] $entrances Привязанные входы к квартире
 *
 * @property-read FlatBlock[] $blocks Блокировки квартиры
 *
 * @property-read HouseSubscriber[] $subscribers Привязанные абоненты к квартире
 * @property-read HouseKey[] $keys Привязанные ключи к квартире
 * @property-read DeviceCamera[] $cameras Привязанные камеры к квартире
 */
class HouseFlat extends Entity
{
    /**
     * @use RepositoryTrait<HouseFlatRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_flats';

    public static string $columnId = 'house_flat_id';

    /**
     * @return OneToOneRelationship<AddressHouse>
     */
    public function house(): OneToOneRelationship
    {
        return $this->oneToOne(AddressHouse::class, 'address_house_id', 'address_house_id');
    }

    /**
     * @return ManyToManyRelationship<HouseEntrance>
     */
    public function entrances(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseEntrance::class, 'houses_entrances_flats', localRelation: 'house_flat_id', foreignRelation: 'house_entrance_id');
    }

    /**
     * @return OneToManyRelationship<FlatBlock>
     */
    public function blocks(): OneToManyRelationship
    {
        return $this->oneToMany(FlatBlock::class, 'flat_id', 'house_flat_id');
    }

    /**
     * @return ManyToManyRelationship<HouseSubscriber>
     */
    public function subscribers(): ManyToManyRelationship
    {
        return $this->manyToMany(HouseSubscriber::class, 'houses_flats_subscribers', localRelation: 'house_flat_id', foreignRelation: 'house_subscriber_id');
    }

    /**
     * @return OneToManyRelationship<HouseKey>
     */
    public function keys(): OneToManyRelationship
    {
        return $this->oneToMany(HouseKey::class, 'access_to', 'house_flat_id', criteria()->equal('access_type', 2));
    }

    /**
     * @return ManyToManyRelationship<DeviceCamera>
     */
    public function cameras(): ManyToManyRelationship
    {
        return $this->manyToMany(DeviceCamera::class, 'houses_cameras_flats', localRelation: 'house_flat_id', foreignRelation: 'camera_id');
    }

    public function block(int $service, int $status, ?string $cause = null): FlatBlock
    {
        $block = new FlatBlock();

        $block->flat_id = $this->house_flat_id;

        $block->service = $service;
        $block->status = $status;

        $block->cause = $cause;

        $block->insert();

        return $block;
    }

    public static function getColumns(): array
    {
        return [
            self::$columnId => rule()->id(),

            'address_house_id' => rule()->id(),

            'floor' => rule()->int(),

            'flat' => rule()->required()->int()->clamp(0)->nonNullable(),

            'code' => rule()->string()->clamp(5, 5),

            'plog' => rule()->int(),

            'open_code' => rule()->string(),

            'auto_open' => rule()->int(),

            'white_rabbit' => rule()->int(),

            'sip_enabled' => rule()->int(),
            'sip_password' => rule()->string(),

            'last_opened' => rule()->int(),
            'cms_enabled' => rule()->int(),

            'open_code_enabled' => rule()->int(),

            'comment' => rule()->string()
        ];
    }
}