<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\User;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Model\Role;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Пользователь роль
 */
#[Controller('/admin/user/{id}/role')]
readonly class UserRoleController extends AdminRbtController
{
    /**
     * Получить список ролей пользователя
     * 
     * @param int $id Идентификатор пользователя
     */
    #[Get]
    public function index(int $id): ResponseInterface
    {
        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        return self::success($user->roles);
    }

    /**
     * Привязать роль к пользователю
     * 
     * @param int $id Идентификатор пользователя
     * @param int $role_id Идентификатор роли
     */
    #[Post('/{role_id}')]
    public function store(int $id, int $role_id): ResponseInterface
    {
        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        $role = Role::findById($role_id);

        if (!$role) {
            return self::error('Не удалось найти роль', 404);
        }

        if (!$user->roles()->add($role)) {
            return self::error('Не удалось привязать роль к пользователю', 400);
        }

        return self::success();
    }

    /**
     * Отвязать роль от пользователя
     * 
     * @param int $id Идентификатор пользователя
     * @param int $role_id Идентификатор роли
     */
    #[Delete('/{role_id}')]
    public function delete(int $id, int $role_id): ResponseInterface
    {
        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        $role = Role::findById($role_id);

        if (!$role) {
            return self::error('Не удалось найти роль', 404);
        }

        if (!$user->roles()->remove($role)) {
            return self::error('Не удалось отвязать роль от пользователя', 400);
        }

        return self::success();
    }
}