<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Model\Audit;
use Selpol\Entity\Repository\Core\CoreUserRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $uid
 *
 * @property string $login
 * @property string $password
 *
 * @property int<0, 1> $enabled
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
    use RepositoryTrait, RelationshipTrait;

    public static string $table = 'core_users';

    public static string $columnId = 'uid';

    public function jsonSerialize(): array
    {
        $value = $this->getValue();

        if (array_key_exists('password', $value))
            unset($value['password']);

        return $value;
    }

    /**
     * @return OneToManyRelationship<CoreAuth>
     */
    public function getAuths(): OneToManyRelationship
    {
        return $this->oneToMany(CoreAuth::class, 'user_id', 'uid');
    }

    /**
     * @return OneToManyRelationship<Audit>
     */
    public function getAudits(): OneToManyRelationship
    {
        return $this->oneToMany(Audit::class, 'user_id', 'uid');
    }

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