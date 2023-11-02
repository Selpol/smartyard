<?php

namespace Selpol\Controller\Api\server;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Sip\SipServer;

readonly class sip extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::TRUE('servers', SipServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): array
    {
        $sipServer = new SipServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable()
        ]));

        if ($sipServer->insert())
            return self::TRUE('id', $sipServer->id);

        return self::FALSE('Не удалось создать');
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

        $sipServer = SipServer::findById($validate['_id'], setting: setting()->nonNullable());

        $sipServer->title = $validate['title'];
        $sipServer->type = $validate['type'];

        $sipServer->trunk = $validate['trunk'];

        $sipServer->external_ip = $validate['external_ip'];
        $sipServer->internal_ip = $validate['internal_ip'];

        if ($sipServer->update())
            return self::TRUE('id', $sipServer->id);

        return self::FALSE('Не удалось обновить');
    }

    public static function DELETE(array $params): array
    {
        $sipServer = SipServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($sipServer->delete())
            return self::TRUE('id', $sipServer->id);

        return self::FALSE('Не удалось удалить');
    }

    public static function index(): array
    {
        return ['GET' => '[Sip] Получить список серверов', 'POST' => '[Sip] Добавить сервер', 'PUT' => '[Sip] Обновить сервер', 'DELETE' => '[Sip] Удалить сервер'];
    }
}