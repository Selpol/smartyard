<?php

namespace api\role;

use api\api;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Rule;

class userRole extends api
{
    public static function GET($params)
    {
        $id = Rule::id()->onItem('_id', $params);

        return self::SUCCESS('roles', container(RoleFeature::class)->findRolesForUser($id));
    }

    public static function POST($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()], 'roleId' => [Rule::id()]]);

        return self::ANSWER(container(RoleFeature::class)->addRoleToUser($validate['_id'], $validate['roleId']));
    }

    public static function DELETE($params)
    {
        $validate = validator($params, ['_id' => [Rule::id()], 'roleId' => [Rule::id()]]);

        return self::ANSWER(container(RoleFeature::class)->deleteRoleFromUser($validate['_id'], $validate['roleId']));
    }

    public static function index(): array
    {
        return ['GET' => '#same(role,userRole,GET)', 'POST' => '#same(role,userRole,POST)', 'DELETE' => '#same(role,userRole,DELETE)'];
    }
}