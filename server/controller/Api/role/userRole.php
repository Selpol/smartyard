<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\RoleRepository;
use Selpol\Feature\Role\RoleFeature;

readonly class userRole extends Api
{
    public static function GET(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        return self::TRUE('roles', container(RoleRepository::class)->findByUserId($id));
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