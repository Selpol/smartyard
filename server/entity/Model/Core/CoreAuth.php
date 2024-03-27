<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Repository\Core\CoreAuthRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $token
 *
 * @property int $user_id
 * @property string $user_agent
 * @property string $user_ip
 *
 * @property bool|int $remember_me
 *
 * @property bool|int $status
 *
 * @property string $last_access_to
 *
 * @property string $created_at
 * @property string $updated_at
 */
class CoreAuth extends Entity
{
    /**
     * @use RepositoryTrait<CoreAuthRepository>
     */
    use RepositoryTrait;

    public static string $table = 'core_auth';

    public static ?string $columnCreateAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public function jsonSerialize(): array
    {
        $value = $this->getValue();

        if (array_key_exists('token', $value))
            unset($value['token']);

        return $value;
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'token' => rule()->required()->string()->nonNullable(),

            'user_id' => rule()->id(),
            'user_agent' => rule()->required()->string()->nonNullable(),
            'user_ip' => rule()->required()->ipV4()->nonNullable(),

            'remember_me' => rule()->required()->int()->nonNullable(),

            'status' => rule()->required()->int()->nonNullable(),

            'last_access_to' => rule()->string(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}