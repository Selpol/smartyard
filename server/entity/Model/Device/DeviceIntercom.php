<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $house_domophone_id
 *
 * @property int $enabled
 *
 * @property string $model
 * @property string $server
 * @property string $url
 * @property string $credentials
 * @property string $dtmf
 *
 * @property int $first_time
 *
 * @property int|null $nat
 *
 * @property string|null $ip
 *
 * @property string|null $comment
 *
 * @property string|null $sos_number
 *
 * @property string|null $device_id
 * @property string|null $device_model
 * @property string|null $device_software_version
 * @property string|null $device_hardware_version
 *
 * @property string|null $config
 *
 * @property bool $hidden
 * 
 * @property-read HouseEntrance[] $entrances
 */
class DeviceIntercom extends Entity
{
    /**
     * @use RepositoryTrait<DeviceIntercomRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'houses_domophones';

    public static string $columnId = 'house_domophone_id';

    public static ?array $fillable = [
        'enabled' => true,

        'model' => true,
        'server' => true,
        'url' => true,
        'credentials' => true,
        'dtmf' => true,

        'first_time' => true,

        'nat' => true,

        'ip' => true,

        'comment' => true,

        'sos_number' => true,

        'config' => true,

        'hidden' => true
    ];

    /**
     * @return OneToManyRelationship<HouseEntrance>
     */
    public function entrances(): OneToManyRelationship
    {
        return $this->oneToMany(HouseEntrance::class, 'house_domophone_id', 'house_domophone_id');
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'enabled' => rule()->required()->int()->nonNullable(),

            'model' => rule()->required()->in(array_keys(IntercomModel::models()))->nonNullable(),
            'server' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'credentials' => rule()->required()->string()->nonNullable(),
            'dtmf' => rule()->required()->string()->in(["*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"])->nonNullable(),

            'first_time' => rule()->required()->int()->nonNullable(),

            'nat' => rule()->int(),

            'ip' => rule()->ipV4(),

            'comment' => rule()->string(),

            'sos_number' => rule()->string(),

            'config' => rule()->string(),

            'device_id' => rule()->string()->clamp(0, 128),
            'device_model' => rule()->string()->clamp(0, 64),
            'device_software_version' => rule()->string()->clamp(0, 64),
            'device_hardware_version' => rule()->string()->clamp(0, 64),

            'hidden' => rule()->bool()
        ];
    }
}