<?php

namespace api\server;

use api\api;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Repository\Frs\FrsServerRepository;
use Selpol\Validator\Rule;

class frs extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'page' => [Rule::int(), Rule::min(0), Rule::max()],
            'size' => [Rule::int(), Rule::min(0), Rule::max(1000)]
        ]);

        return container(FrsServerRepository::class)->fetchPaginate($validate['page'], $validate['size']);
    }

    public static function POST($params)
    {
        $frsServer = new FrsServer(validator($params, [
            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()]
        ]));

        $result = container(FrsServerRepository::class)->inser($frsServer);

        if ($result)
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT($params)
    {
        $validate = validator($params, [
            '_id' => [Rule::id()],

            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()]
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
        $validate = validator($params, ['_id' => [Rule::id()]]);

        $frsServer = container(FrsServerRepository::class)->findById($validate['_id']);

        $result = container(FrsServerRepository::class)->delete($frsServer);

        if ($result)
            return self::SUCCESS('id', $frsServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET', 'POST', 'PUT', 'DELETE'];
    }
}