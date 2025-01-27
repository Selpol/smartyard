<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Role\RoleStoreRequest;
use Selpol\Controller\Request\Admin\Role\RoleUpdateRequest;
use Selpol\Entity\Model\Role;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Роль
 */
#[Controller('/admin/role')]
readonly class RoleController extends AdminRbtController
{
    /**
     * Получить список ролей
     */
    #[Get]
    public function index(): ResponseInterface
    {
        return self::success(Role::fetchAll());
    }

    /**
     * Создать новую роль
     */
    #[Post]
    public function store(RoleStoreRequest $request): ResponseInterface
    {
        $role = new Role();

        $role->title = $request->title;
        $role->description = $request->description;

        $role->insert();

        return self::success($role->id);
    }

    /**
     * Обновить роль
     */
    #[Put('/{id}')]
    public function update(RoleUpdateRequest $request): ResponseInterface
    {
        $role = Role::findById($request->id);

        if (!$role) {
            return self::error('Не удалось обновить роль', 404);
        }

        $role->title = $request->title;
        $role->description = $request->description;

        $role->update();

        return self::success();
    }

    /**
     * Удалить роль
     * 
     * @param int $id Идентификатор роли
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $role = Role::findById($id);

        if (!$role) {
            return self::error('Не удалось найти роль', 404);
        }

        if (!$role->safeDelete()) {
            return self::error('Не удалось удалить роль', 400);
        }

        return self::success();
    }
}
