<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;

class rolePermission extends api
{
    public static function GET($params)
    {
        $id = rule()->id()->onItem('_id', $params);

        return self::SUCCESS('permissions', container(RoleFeature::class)->findPermissionsForRole($id));
    }

    public static function POST($params)
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->addPermissionToRole($validate['_id'], $validate['permissionId']));
    }

    public static function DELETE($params)
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->deletePermissionFromRole($validate['_id'], $validate['permissionId']));
    }

    public static function index(): array
    {
        return ['GET' => '[Роль-Права] Получить список', 'POST' => '[Роль-Права] Добавить связь', 'DELETE' => '[Роль-Права] Удалить связь'];
    }
}