<?php

namespace Selpol\Controller\Api\server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Dvr\DvrServer;

readonly class dvr extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::success(DvrServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): ResponseInterface
    {
        $dvrServer = new DvrServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['flussonic', 'trassir'])->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'token' => rule()->required()->string()->max(1024)->nonNullable(),
            'credentials' => rule()->required()->string()->max(1024)->nonNullable()
        ]));

        if ($dvrServer->insert())
            return self::success($dvrServer->id);

        return self::error('Не удалось создать Dvr сервер', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['flussonic', 'trassir'])->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'token' => rule()->required()->string()->max(1024)->nonNullable(),
            'credentials' => rule()->string()->max(1024),
        ]);

        $dvrServer = DvrServer::findById($validate['_id'], setting: setting()->nonNullable());

        $dvrServer->title = $validate['title'];
        $dvrServer->type = $validate['type'];

        $dvrServer->url = $validate['url'];

        $dvrServer->token = $validate['token'];

        if ($validate['credentials'] && !str_contains($validate['credentials'], '*'))
            $dvrServer->credentials = $validate['credentials'];

        if ($dvrServer->update())
            return self::success($dvrServer->id);

        return self::error('Не удалось обновить Dvr сервер', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $dvrServer = DvrServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($dvrServer->delete())
            return self::success();

        return self::error('Не удалось удалить Dvr сервер', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Dvr] Получить список серверов', 'POST' => '[Dvr] Добавить сервер', 'PUT' => '[Dvr] Обновить сервер', 'DELETE' => '[Dvr] Удалить сервер'];
    }
}