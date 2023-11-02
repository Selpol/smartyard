<?php

namespace Selpol\Controller\Api\subscribers;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;

readonly class key extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(HouseKey::findById($params['_id'], setting: setting()->nonNullable())->toArrayMap([
            'house_rfid_id' => 'keyId',
            'rfid' => 'rfId',
            'access_type' => 'accessType',
            'access_to' => 'accessTo',
            'last_seen' => 'lastSeen',
            'comments' => 'comments'
        ]));
    }

    public static function POST(array $params): ResponseInterface
    {
        $key = new HouseKey();

        $key->rfid = $params['rfId'];

        $key->access_type = $params['accessType'];
        $key->access_to = $params['accessTo'];

        $key->comments = $params['comments'];

        if ($key->insert()) {
            task(new IntercomAddKeyTask($key->rfid, $key->access_to))->sync();

            return self::success($key->house_rfid_id);
        }

        return self::error('Не удалось добавть ключ', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $key = HouseKey::findById($params['_id'], setting: setting()->nonNullable());

        $key->comments = $params['comments'];

        if ($key->update())
            return self::success($key->house_rfid_id);

        return self::error('Не удалось обновить ключ', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $key = HouseKey::findById($params['_id'], setting: setting()->nonNullable());

        if ($key?->delete()) {
            task(new IntercomDeleteKeyTask($key->rfid, $key->access_to))->sync();

            return self::success();
        }

        return self::error('Не удалось удалить ключ', 400);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Ключи] Получить ключ', 'PUT' => '[Ключи] Обновить ключ', 'POST' => '[Ключи] Создать ключ', 'DELETE' => '[Ключи] Удалить ключ'];
    }
}