<?php

namespace Selpol\Controller\Api\role;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Feature\Role\RoleFeature;

readonly class userPermission extends Api
{
    public static function GET(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        return self::SUCCESS('permissions', container(PermissionRepository::class)->findByUserId($id));
    }

    public static function POST(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->addPermissionToUser($validate['_id'], $validate['permissionId']));
    }

    public static function DELETE(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        return self::ANSWER(container(RoleFeature::class)->deletePermissionFromUser($validate['_id'], $validate['permissionId']));
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь-Права] Получить список', 'POST' => '[Пользователь-Права] Добавить связь', 'DELETE' => '[Пользователь-Права] Удалить связь'];
    }
}