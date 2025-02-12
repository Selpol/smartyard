<?php

namespace Selpol\Controller\Api\server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Server\StreamerServer;

readonly class streamer extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::success(StreamerServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): ResponseInterface
    {
        $sipServer = new StreamerServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->string()->url()->nonNullable()
        ]));

        if ($sipServer->safeInsert()) {
            return self::success($sipServer->id);
        }

        return self::error('Не удалось создать стример сервер', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->string()->url()->nonNullable(),
        ]);

        $sipServer = StreamerServer::findById($validate['_id'], setting: setting()->nonNullable());

        $sipServer->title = $validate['title'];
        $sipServer->url = $validate['url'];

        if ($sipServer->safeUpdate()) {
            return self::success($sipServer->id);
        }

        return self::error('Не удалось обновить стример сервер', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $sipServer = StreamerServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($sipServer->safeDelete()) {
            return self::success();
        }

        return self::error('Не удалось удалить стример сервер', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Deprecated] [Стример] Получить список серверов', 'POST' => '[Deprecated] [Стример] Добавить сервер', 'PUT' => '[Deprecated] [Стример] Обновить сервер', 'DELETE' => '[Deprecated] [Стример] Удалить сервер'];
    }
}