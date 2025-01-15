<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Dvr;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository\Dvr\DvrServerRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
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
 * @property string $token - Используется для получение видеопотока
 * @property string $credentials - Используется для управлением DVR-сервером
 *
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property-read DeviceCamera[] $cameras
 */
class DvrServer extends Entity
{
    /**
     * @use RepositoryTrait<DvrServerRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'dvr_servers';

    public static string $columnIdStrategy = 'dvr_servers_id_seq';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return OneToManyRelationship<DeviceCamera>
     */
    public function cameras(): OneToManyRelationship
    {
        return $this->oneToMany(DeviceCamera::class, 'dvr_server_id', 'id');
    }

    public function jsonSerialize(): array
    {
        $value = $this->getValue();

        if (array_key_exists('credentials', $value)) {
            unset($value['credentials']);
        }

        return $value;
    }

    public function credentials(): array
    {
        return array_reduce(explode('&', $this->credentials), static function (array $previous, string $current) {
            $value = explode('=', $current);

            $previous[$value[0]] = $value[1];

            return $previous;
        }, []);
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'type' => rule()->required()->in(['flussonic', 'trassir'])->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'token' => rule()->required()->string()->nonNullable(),
            'credentials' => rule()->required()->string()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}