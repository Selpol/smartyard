<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Dvr\DvrServer;

readonly class dvr extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('servers', DvrServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): array
    {
        $dvrServer = new DvrServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['flussonic', 'trassir'])->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'token' => rule()->required()->string()->max(1024)->nonNullable()
        ]));

        if ($dvrServer->insert())
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT(array $params): array
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['flussonic', 'trassir'])->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'token' => rule()->required()->string()->max(1024)->nonNullable()
        ]);

        $dvrServer = DvrServer::findById($validate['_id'], setting: setting()->nonNullable());

        $dvrServer->title = $validate['title'];
        $dvrServer->type = $validate['type'];

        $dvrServer->url = $validate['url'];

        $dvrServer->token = $validate['token'];

        if ($dvrServer->update())
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE(array $params): array
    {
        $dvrServer = DvrServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($dvrServer?->delete())
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Dvr] Получить список серверов', 'POST' => '[Dvr] Добавить сервер', 'PUT' => '[Dvr] Обновить сервер', 'DELETE' => '[Dvr] Удалить сервер'];
    }
}