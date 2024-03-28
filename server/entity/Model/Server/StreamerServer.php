<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Server;

use Selpol\Entity\Repository\Server\StreamerServerRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $url
 *
 * @property string $created_at
 * * @property string $updated_at
 */
class StreamerServer extends Entity
{
    /**
     * @use RepositoryTrait<StreamerServerRepository>
     */
    use RepositoryTrait;

    public static string $table = 'streamer_servers';

    public static ?string $columnCreateAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'url' => rule()->required()->url()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}