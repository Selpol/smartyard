<?php declare(strict_types=1);

namespace Selpol\Entity\Model\House;

use Selpol\Entity\Repository\House\HouseFlatRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $house_flat_id
 * @property int $address_house_id
 *
 * @property int|null $floor
 *
 * @property string|int $flat
 *
 * @property string|null $code
 *
 * @property int|null $plog
 *
 * @property string|null $open_code
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
 * @property string|null $comment
 */
class HouseFlat extends Entity
{
    /**
     * @use RepositoryTrait<HouseFlatRepository>
     */
    use RepositoryTrait;

    public static string $table = 'houses_flats';

    public static string $columnId = 'house_flat_id';

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

            'comment' => rule()->string()
        ];
    }
}