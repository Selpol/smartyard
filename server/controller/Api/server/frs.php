<?php

namespace Selpol\Controller\Api\server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Frs\FrsServer;

readonly class frs extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::success(FrsServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): ResponseInterface
    {
        $frsServer = new FrsServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->url()->nonNullable()
        ]));

        if ($frsServer->insert())
            return self::success($frsServer->id);

        return self::error('Не удалось создать Frs сервер', 400);
    }

    public static function PUT(array $params): ResponseInterface
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
            return self::success($frsServer->id);

        return self::error('Не удалось обновить Frs сервер', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $frsServer = FrsServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($frsServer?->delete())
            return self::success();

        return self::error('Не удалось удалить Frs сервер', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Frs] Получить список серверов', 'POST' => '[Frs] Добавить сервер', 'PUT' => '[Frs] Обновить сервер', 'DELETE' => '[Frs] Удалить сервер'];
    }
}