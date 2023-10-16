<?php

namespace Selpol\Controller\Api\subscribers;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Repository\House\HouseKeyRepository;
use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;

class key extends Api
{
    public static function GET(array $params): array
    {
        return self::SUCCESS('key', container(HouseKeyRepository::class)->findById($params['_id'])->toArrayMap([
            'house_rfid_id' => 'keyId',
            'rfid' => 'rfId',
            'access_type' => 'accessType',
            'access_to' => 'accessTo',
            'last_seen' => 'lastSeen',
            'comments' => 'comments'
        ]));
    }

    public static function POST(array $params): array
    {
        $key = new HouseKey();

        $key->rfid = $params['rfId'];

        $key->access_type = $params['accessType'];
        $key->access_to = $params['accessTo'];

        $key->comments = $params['comments'];

        if (container(HouseKeyRepository::class)->insert($key)) {
            task(new IntercomAddKeyTask($key->rfid, $key->access_to))->sync();

            return self::ANSWER($key->house_rfid_id, 'key');
        }

        return self::ERROR('Не удалось добавить ключ');
    }

    public static function PUT(array $params): array
    {
        $key = container(HouseKeyRepository::class)->findById($params['_id']);

        $key->comments = $params['comments'];

        return self::ANSWER(container(HouseKeyRepository::class)->update($key));
    }

    public static function DELETE(array $params): array
    {
        $key = container(HouseKeyRepository::class)->findById($params['_id']);

        if (container(HouseKeyRepository::class)->delete($key)) {
            task(new IntercomDeleteKeyTask($key->rfid, $key->access_to))->sync();

            return self::ANSWER();
        }

        return self::ANSWER(false);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Ключи] Получить ключ', 'PUT' => '[Ключи] Обновить ключ', 'POST' => '[Ключи] Создать ключ', 'DELETE' => '[Ключи] Удалить ключ'];
    }
}