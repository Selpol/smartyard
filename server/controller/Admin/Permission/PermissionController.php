<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Permission;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\PermissionUpdateRequest;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Права
 */
#[Controller('/admin/permission')]
readonly class PermissionController extends AdminRbtController
{
    /**
     * Получить список прав
     */
    #[Get]
    public function index(): ResponseInterface
    {
        return self::success(Permission::fetchAll(criteria()->asc('title'), setting()->columns(['id', 'title', 'description'])));
    }

    /**
     * Обновить право
     */
    #[Put('/{id}')]
    public function update(PermissionUpdateRequest $request): ResponseInterface
    {
        $permission = Permission::findById($request->id);

        if (!$permission) {
            return self::error('Не удалось найти право', 404);
        }

        $permission->description = $request->description;

        $permission->update();

        return self::success();
    }
}