<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\User;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Пользователь права
 */
#[Controller('/admin/user/permission/{id}')]
readonly class UserPermissionController extends AdminRbtController
{
    /**
     * Получить список прав пользователя
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

        return self::success($user->permissions()->fetchAll(criteria()->asc('title'), setting: setting()->columns(['id', 'title', 'description'])));
    }

    /**
     * Привязать право к пользователю
     * 
     * @param int $id Идентификатор пользователя
     * @param int $permission_id Идентификатор права
     */
    #[Post('/{permission_id}')]
    public function store(int $id, int $permission_id): ResponseInterface
    {
        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        $permission = Permission::findById($permission_id);

        if (!$permission) {
            return self::error('Не удалось найти право', 404);
        }

        if (!$user->permissions()->add($permission)) {
            return self::error('Не удалось привязать право к пользователю', 400);
        }

        return self::success();
    }

    /**
     * Отвязать право от пользователя
     * 
     * @param int $id Идентификатор пользователя
     * @param int $permission_id Идентификатор права
     */
    #[Delete('/{permission_id}')]
    public function delete(int $id, int $permission_id): ResponseInterface
    {
        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        $permission = Permission::findById($permission_id);

        if (!$permission) {
            return self::error('Не удалось найти право', 404);
        }

        if (!$user->permissions()->remove($permission)) {
            return self::error('Не удалось отвязать право от пользователя', 400);
        }

        return self::success();
    }
}