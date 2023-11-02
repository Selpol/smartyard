<?php

namespace Selpol\Controller\Api\role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Role\RoleFeature;

readonly class userRole extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(\Selpol\Entity\Model\Role::getRepository()->findByUserId(rule()->id()->onItem('_id', $params)));
    }

    public static function POST(array $params): ResponseInterface
    {
        $validate = validator($params, ['_id' => rule()->id(), 'roleId' => rule()->id()]);

        if (container(RoleFeature::class)->addRoleToUser($validate['_id'], $validate['roleId']))
            return self::success($validate['_id']);

        return self::error('Не удалось привязать группу к пользователю');
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $validate = validator($params, ['_id' => rule()->id(), 'roleId' => rule()->id()]);

        if (container(RoleFeature::class)->deleteRoleFromUser($validate['_id'], $validate['roleId']))
            return self::success();

        return self::error('Не удалось отвязать группу от пользователя', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь-Роль] Получить список', 'POST' => '[Пользователь-Роль] Добавить связь', 'DELETE' => '[Пользователь-Роль] Удалить связь'];
    }
}