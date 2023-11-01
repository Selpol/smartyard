<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Repository\Core\CoreUserRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $uid
 *
 * @property string $login
 * @property string $password
 *
 * @property int $enabled
 *
 * @property string|null $real_name
 * @property string|null $e_mail
 * @property string|null $phone
 * @property string|null $tg
 * @property string|null $notification
 * @property string|null $default_route
 *
 * @property int|null $last_login
 */
class CoreUser extends Entity
{
    /**
     * @use RepositoryTrait<CoreUserRepository>
     */
    use RepositoryTrait;

    public static string $table = 'core_users';

    public static string $columnId = 'uid';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'login' => rule()->required()->string()->nonNullable(),
            'password' => rule()->required()->string()->nonNullable(),

            'enabled' => rule()->required()->int()->nonNullable(),

            'real_name' => rule()->string(),
            'e_mail' => rule()->string(),
            'phone' => rule()->string(),
            'tg' => rule()->string(),
            'notification' => rule()->string(),
            'default_route' => rule()->string(),

            'last_login' => rule()->int()
        ];
    }
}