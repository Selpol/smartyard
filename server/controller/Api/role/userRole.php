<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Role\RoleFeature;

class userRole extends Api
{
    public static function GET(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        return self::SUCCESS('roles', container(RoleFeature::class)->findRolesForUser($id));
    }

    public static function POST(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id(), 'roleId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->addRoleToUser($validate['_id'], $validate['roleId']));
    }

    public static function DELETE(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id(), 'roleId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->deleteRoleFromUser($validate['_id'], $validate['roleId']));
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь-Роль] Получить список', 'POST' => '[Пользователь-Роль] Добавить связь', 'DELETE' => '[Пользователь-Роль] Удалить связь'];
    }
}