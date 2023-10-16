<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Repository\Frs\FrsServerRepository;

class frs extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('servers', container(FrsServerRepository::class)->fetchPaginate($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): array
    {
        $frsServer = new FrsServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->url()->nonNullable()
        ]));

        if (container(FrsServerRepository::class)->inser($frsServer))
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

        $frsServer = container(FrsServerRepository::class)->findById($validate['_id']);

        $frsServer->title = $validate['title'];
        $frsServer->url = $validate['url'];

        if (container(FrsServerRepository::class)->update($frsServer))
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        $frsServer = container(FrsServerRepository::class)->findById($id);

        if (container(FrsServerRepository::class)->delete($frsServer))
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Frs] Получить список серверов', 'POST' => '[Frs] Добавить сервер', 'PUT' => '[Frs] Обновить сервер', 'DELETE' => '[Frs] Удалить сервер'];
    }
}