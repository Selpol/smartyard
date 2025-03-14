<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Sip;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository\Sip\SipServerRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $type
 *
 * @property string $trunk
 *
 * @property string $external_ip
 * @property string $internal_ip
 *
 * @property int $external_port
 * @property int $internal_port
 *
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read DeviceIntercom[] $intercoms
 */
class SipServer extends Entity
{
    /**
     * @use RepositoryTrait<SipServerRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'sip_servers';

    public static string $columnIdStrategy = 'sip_servers_id_seq';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return OneToManyRelationship<DeviceIntercom>
     */
    public function intercoms(): OneToManyRelationship
    {
        return $this->oneToMany(DeviceIntercom::class, 'server', 'internal_ip');
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'type' => rule()->required()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable(),

            'external_port' => rule()->required()->int()->clamp(0, 65535)->nonNullable(),
            'internal_port' => rule()->required()->int()->clamp(0, 65535)->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}