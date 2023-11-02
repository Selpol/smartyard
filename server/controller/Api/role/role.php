<?php

namespace Selpol\Controller\Api\role;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class role extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(\Selpol\Entity\Model\Role::fetchAll());
    }

    public static function POST(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'title' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()],
            'description' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()]
        ]);

        $role = new \Selpol\Entity\Model\Role();

        $role->title = $validate['title'];
        $role->description = $validate['description'];

        if ($role->insert())
            return self::success($role->id);

        return self::error('Не удалось создать группу', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),
            'title' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()],
            'description' => [filter()->fullSpecialChars(), rule()->required()->string()->max(1024)->nonNullable()]
        ]);

        $role = \Selpol\Entity\Model\Role::findById($validate['_id'], setting: setting()->nonNullable());

        if (!$role)
            return self::error('Группа не найдена', 404);

        $role->title = $validate['title'];
        $role->description = $validate['description'];

        if ($role->update())
            return self::success($role->id);

        return self::error('Не удалось обновить группу', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {

        $role = \Selpol\Entity\Model\Role::findById(rule()->id()->onItem('_id', $params));

        if (!$role)
            return self::error('Группа не найдена', 404);

        if ($role->delete())
            return self::success();

        return self::error('Не удалось удалить группу', 400);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Роль] Получить список', 'POST' => '[Роль] Создать роль', 'PUT' => '[Роль] Обновить роль', 'DELETE' => '[Роль] Удалить роль'];
    }
}