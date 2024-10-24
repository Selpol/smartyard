<?php

namespace Selpol\Controller\Api\server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Sip\SipServer;

readonly class sip extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(0, 512),
        ]);

        return self::success(SipServer::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function POST(array $params): ResponseInterface
    {
        $sipServer = new SipServer(validator($params, [
            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable(),

            'external_port' => rule()->required()->int()->clamp(0, 65535)->nonNullable(),
            'internal_port' => rule()->required()->int()->clamp(0, 65535)->nonNullable()
        ]));

        if ($sipServer->safeInsert()) {
            return self::success($sipServer->id);
        }

        return self::error('Не удалось создать Sip сервер', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'title' => rule()->required()->string()->max(1024)->nonNullable(),
            'type' => rule()->required()->string()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable(),

            'external_port' => rule()->required()->int()->clamp(0, 65535)->nonNullable(),
            'internal_port' => rule()->required()->int()->clamp(0, 65535)->nonNullable()
        ]);

        $sipServer = SipServer::findById($validate['_id'], setting: setting()->nonNullable());

        $sipServer->title = $validate['title'];
        $sipServer->type = $validate['type'];

        $sipServer->trunk = $validate['trunk'];

        $sipServer->external_ip = $validate['external_ip'];
        $sipServer->internal_ip = $validate['internal_ip'];

        $sipServer->external_port = $validate['external_port'];
        $sipServer->internal_port = $validate['internal_port'];

        if ($sipServer->safeUpdate()) {
            return self::success($sipServer->id);
        }

        return self::error('Не удалось обновить Sip сервер', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $sipServer = SipServer::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if ($sipServer->safeDelete()) {
            return self::success();
        }

        return self::error('Не удалось удалить Sip сервер', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Sip] Получить список серверов', 'POST' => '[Sip] Добавить сервер', 'PUT' => '[Sip] Обновить сервер', 'DELETE' => '[Sip] Удалить сервер'];
    }
}