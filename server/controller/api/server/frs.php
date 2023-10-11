<?php

namespace api\server;

use api\api;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Repository\Frs\FrsServerRepository;

class frs extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('servers', container(FrsServerRepository::class)->fetchPaginate($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST($params)
    {
        $frsServer = new FrsServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->url()->nonNullable()
        ]));

        $result = container(FrsServerRepository::class)->inser($frsServer);

        if ($result)
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT($params)
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'url' => rule()->required()->url()->nonNullable()
        ]);

        $frsServer = container(FrsServerRepository::class)->findById($validate['_id']);

        $frsServer->title = $validate['title'];
        $frsServer->url = $validate['url'];

        $result = container(FrsServerRepository::class)->update($frsServer);

        if ($result)
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE($params)
    {
        $id = rule()->id()->onItem('_id', $params);

        $frsServer = container(FrsServerRepository::class)->findById($id);

        $result = container(FrsServerRepository::class)->delete($frsServer);

        if ($result)
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Frs] Получить список серверов', 'POST' => '[Frs] Добавить сервер', 'PUT' => '[Frs] Обновить сервер', 'DELETE' => '[Frs] Удалить сервер'];
    }
}