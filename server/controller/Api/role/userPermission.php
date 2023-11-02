<?php

namespace Selpol\Controller\Api\role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Role\RoleFeature;

readonly class userPermission extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(\Selpol\Entity\Model\Permission::getRepository()->findByUserId(rule()->id()->onItem('_id', $params)));
    }

    public static function POST(array $params): ResponseInterface
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        if (container(RoleFeature::class)->addPermissionToUser($validate['_id'], $validate['permissionId']))
            return self::success($validate['_id']);

        return self::error('Не удалось привязать право к пользователю', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $validate = validator($params, ['_id' => rule()->id(), 'permissionId' => rule()->id()]);

        if (container(RoleFeature::class)->deletePermissionFromUser($validate['_id'], $validate['permissionId']))
            return self::success();

        return self::error('Не удалось отвязать право от пользователя', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь-Права] Получить список', 'POST' => '[Пользователь-Права] Добавить связь', 'DELETE' => '[Пользователь-Права] Удалить связь'];
    }
}