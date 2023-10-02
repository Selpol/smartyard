<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Rule;

class userPermission extends api
{
    public static function GET($params)
    {
        $id = Rule::id()->onItem('_id', $params);

        return self::SUCCESS('permissions', container(RoleFeature::class)->findPermissionsForUser($id));
    }

    public static function POST($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()], 'permissionId' => [Rule::id()]]);

        return self::ANSWER(container(RoleFeature::class)->addPermissionToUser($validate['_id'], $validate['permissionId']));
    }

    public static function DELETE($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()], 'permissionId' => [Rule::id()]]);

        return self::ANSWER(container(RoleFeature::class)->deletePermissionFromUser($validate['_id'], $validate['permissionId']));
    }

    public static function index(): array
    {
        return ['GET' => '#same(role,userPermission,GET)', 'POST' => '#same(role,userPermission,POST)', 'DELETE' => '#same(role,userPermission,DELETE)'];
    }
}