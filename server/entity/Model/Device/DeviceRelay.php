<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Device;

use Selpol\Entity\Repository\Device\DeviceRelayRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $url
 * @property string $credential
 *
 * @property string $created_at
 * @property string $updated_at
 */
class DeviceRelay extends Entity
{
    /**
     * @use RepositoryTrait<DeviceRelayRepository>
     */
    use RepositoryTrait;

    public static string $table = 'relays';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),
            'credential' => rule()->required()->string()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}