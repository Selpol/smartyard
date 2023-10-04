<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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
    public static string $table = 'core_users';

    public static string $columnId = 'uid';

    protected static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'login' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'password' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'enabled' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'real_name' => [Rule::length()],
            'e_mail' => [Rule::length()],
            'phone' => [Rule::length()],
            'tg' => [Rule::length()],
            'notification' => [Rule::length()],
            'default_route' => [Rule::length()],

            'last_login' => [Rule::int()]
        ];
    }
}