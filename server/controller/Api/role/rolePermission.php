<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Role\RoleFeature;

readonly class rolePermission extends Api
{
    public static function GET(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        return self::SUCCESS('permissions', container(RoleFeature::class)->findPermissionsForRole($id));
    }

    public static function POST(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->addPermissionToRole($validate['_id'], $validate['permissionId']));
    }

    public static function DELETE(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->deletePermissionFromRole($validate['_id'], $validate['permissionId']));
    }

    public static function index(): array
    {
        return ['GET' => '[Роль-Права] Получить список', 'POST' => '[Роль-Права] Добавить связь', 'DELETE' => '[Роль-Права] Удалить связь'];
    }
}