<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Entity\Repository\Sip\SipServerRepository;

class sip extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::SUCCESS('servers', container(SipServerRepository::class)->fetchPaginate($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): array
    {
        $dvrServer = new SipServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable()
        ]));

        if (container(SipServerRepository::class)->insert($dvrServer))
            return self::SUCCESS('id', $dvrServer->id);

        return self::ERROR('Не удалось создать');
    }

    public static function PUT(array $params): array
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable()
        ]);

        $sipServer = container(SipServerRepository::class)->findById($validate['_id']);

        $sipServer->title = $validate['title'];
        $sipServer->type = $validate['type'];

        $sipServer->trunk = $validate['trunk'];

        $sipServer->external_ip = $validate['external_ip'];
        $sipServer->internal_ip = $validate['internal_ip'];

        if (container(SipServerRepository::class)->update($sipServer))
            return self::SUCCESS('id', $sipServer->id);

        return self::ERROR('Не удалось обновить');
    }

    public static function DELETE(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        $sipServer = container(SipServerRepository::class)->findById($id);

        if (container(SipServerRepository::class)->delete($sipServer))
            return self::SUCCESS('id', $sipServer->id);

        return self::ERROR('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Sip] Получить список серверов', 'POST' => '[Sip] Добавить сервер', 'PUT' => '[Sip] Обновить сервер', 'DELETE' => '[Sip] Удалить сервер'];
    }
}