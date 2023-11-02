<?php

namespace Selpol\Controller\Api\role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Role\RoleFeature;

readonly class rolePermission extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(\Selpol\Entity\Model\Permission::getRepository()->findByRoleId(rule()->id()->onItem('_id', $params)));
    }

    public static function POST(array $params): ResponseInterface
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        if (container(RoleFeature::class)->addPermissionToRole($validate['_id'], $validate['permissionId']))
            return self::success($validate['_id']);

        return self::error('Не удалось привязать права к группе', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        if (container(RoleFeature::class)->deletePermissionFromRole($validate['_id'], $validate['permissionId']))
            return self::success();

        return self::error('Не удалось отвязать право от группы', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Роль-Права] Получить список', 'POST' => '[Роль-Права] Добавить связь', 'DELETE' => '[Роль-Права] Удалить связь'];
    }
}