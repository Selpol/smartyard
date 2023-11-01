<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Dvr;

use Selpol\Entity\Repository\Dvr\DvrServerRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 *
 * @property string $type
 *
 * @property string $url
 *
 * @property string $token
 *
 * @property string $created_at
 * @property string $updated_at
 */
class DvrServer extends Entity
{
    /**
     * @use RepositoryTrait<DvrServerRepository>
     */
    use RepositoryTrait;

    public static string $table = 'dvr_servers';

    public static string $columnIdStrategy = 'dvr_servers_id_seq';

    public static ?string $columnCreateAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'type' => rule()->required()->in(['flussonic', 'trassir'])->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'token' => rule()->required()->string()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}