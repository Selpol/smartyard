<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Rule;

class rolePermission extends api
{
    public static function GET($params)
    {
        $id = Rule::id()->onItem('_id', $params);

        return self::SUCCESS('permissions', container(RoleFeature::class)->findPermissionsForRole($id));
    }

    public static function POST($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()], 'permissionId' => [Rule::id()]]);

        return self::ANSWER(container(RoleFeature::class)->addPermissionToRole($validate['_id'], $validate['permissionId']));
    }

    public static function DELETE($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()], 'permissionId' => [Rule::id()]]);

        return self::ANSWER(container(RoleFeature::class)->deletePermissionFromRole($validate['_id'], $validate['permissionId']));
    }

    public static function index(): array
    {
        return ['GET' => '#same(role,rolePermission,GET)', 'POST' => '#same(role,rolePermission,POST)', 'DELETE' => '#same(role,rolePermission,DELETE)'];
    }
}