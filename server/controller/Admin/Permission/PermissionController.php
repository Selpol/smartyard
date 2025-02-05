<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Permission;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Permission;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

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
}