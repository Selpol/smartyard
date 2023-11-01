<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Frs\FrsServer;

readonly class frs extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('servers', FrsServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): array
    {
        $frsServer = new FrsServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->url()->nonNullable()
        ]));

        if ($frsServer->insert())
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT(array $params): array
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->url()->nonNullable()
        ]);

        $frsServer = FrsServer::findById($validate['_id'], setting: setting()->nonNullable());

        $frsServer->title = $validate['title'];
        $frsServer->url = $validate['url'];

        if ($frsServer->update())
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE(array $params): array
    {
        $frsServer = FrsServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($frsServer?->delete())
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Frs] Получить список серверов', 'POST' => '[Frs] Добавить сервер', 'PUT' => '[Frs] Обновить сервер', 'DELETE' => '[Frs] Удалить сервер'];
    }
}