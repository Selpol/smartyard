<?php

namespace api\server;

use api\api;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Entity\Repository\Dvr\DvrServerRepository;
use Selpol\Validator\Rule;

class dvr extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'page' => [Rule::int(), Rule::min(0), Rule::max()],
            'size' => [Rule::int(), Rule::min(0), Rule::max(1000)]
        ]);

        return self::SUCCESS('servers', container(DvrServerRepository::class)->fetchPaginate($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST($params)
    {
        $dvrServer = new DvrServer(validator($params, [
            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'type' => [Rule::required(), Rule::in(['flussonic', 'trassir']), Rule::nonNullable()],

            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()],

            'token' => [Rule::required(), Rule::length(), Rule::nonNullable()]
        ]));

        $result = container(DvrServerRepository::class)->insert($dvrServer);

        if ($result)
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT($params)
    {
        $validate = validator($params, [
            '_id' => [Rule::id()],

            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'type' => [Rule::required(), Rule::in(['flussonic', 'trassir']), Rule::nonNullable()],

            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()],

            'token' => [Rule::required(), Rule::length(), Rule::nonNullable()]
        ]);

        $dvrServer = container(DvrServerRepository::class)->findById($validate['_id']);

        $dvrServer->title = $validate['title'];
        $dvrServer->type = $validate['type'];

        $dvrServer->url = $validate['url'];

        $dvrServer->token = $validate['token'];

        $result = container(DvrServerRepository::class)->update($dvrServer);

        if ($result)
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE($params)
    {
        $validate = validator($params, [
            '_id' => [Rule::id()]
        ]);

        $dvrServer = container(DvrServerRepository::class)->findById($validate['_id']);

        $result = container(DvrServerRepository::class)->delete($dvrServer);

        if ($result)
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Dvr] Получить список серверов', 'POST' => '[Dvr] Добавить сервер', 'PUT' => '[Dvr] Обновить сервер', 'DELETE' => '[Dvr] Удалить сервер'];
    }
}