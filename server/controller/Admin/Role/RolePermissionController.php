<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Model\Role;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Роль права
 */
#[Controller('/admin/role/{id}/permission')]
readonly class RolePermissionController extends AdminRbtController
{
    /**
     * Получить список прав роли
     * 
     * @param int $id Идентификатор роли
     */
    #[Get]
    public function index(int $id): ResponseInterface
    {
        $role = Role::findById($id);

        if (!$role) {
            return self::error('Не удалось найти роль', 404);
        }

        return self::success($role->permissions()->fetchAll(criteria()->asc('title'), setting: setting()->columns(['id', 'title', 'description'])));
    }

    /**
     * Привязать право к роли
     * 
     * @param int $id Идентификатор роли
     * @param int $permission_id Идентификатор права
     */
    #[Post('/{permission_id}')]
    public function store(int $id, int $permission_id): ResponseInterface
    {
        $role = Role::findById($id);

        if (!$role) {
            return self::error('Не удалось найти роль', 404);
        }

        $permission = Permission::findById($permission_id);

        if (!$permission) {
            return self::error('Не удалось найти право', 404);
        }

        if (!$role->permissions()->add($permission)) {
            return self::error('Не удалось привязать право к роли', 400);
        }

        return self::success();
    }

    /**
     * Отвязать право от роли
     * 
     * @param int $id Идентификатор роли
     * @param int $permission_id Идентификатор права
     */
    #[Delete('/{permission_id}')]
    public function delete(int $id, int $permission_id): ResponseInterface
    {
        $role = Role::findById($id);

        if (!$role) {
            return self::error('Не удалось найти роль', 404);
        }

        $permission = Permission::findById($permission_id);

        if (!$permission) {
            return self::error('Не удалось найти право', 404);
        }

        if (!$role->permissions()->remove($permission)) {
            return self::error('Не удалось отвязать право от роли', 400);
        }

        return self::success();
    }
}