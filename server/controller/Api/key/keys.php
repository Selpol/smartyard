<?php

namespace Selpol\Controller\Api\key;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Service\DatabaseService;

readonly class keys extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'rfid' => rule()->string(),
            'comments' => rule()->string(),

            'page' => [filter()->default(0), rule()->int()->min(0)->max()],
            'size' => [filter()->default(10), rule()->int()->min(1)->max(512)],
        ]);

        $page = HouseKey::fetchPage($validate['page'], $validate['size'], criteria()->like('rfid', $validate['rfid'])->orLike('comments', $validate['comments'])->asc('house_rfid_id')->asc('access_to'));
        $data = $page->getData();

        $flats = [];
        $houses = [];

        $db = container(DatabaseService::class);

        foreach ($data as $key) {
            if ($key->access_type === 2) {
                if (!array_key_exists($key->access_to, $flats))
                    $flats[$key->access_to] = $db->get('SELECT address_house_id, flat FROM houses_flats WHERE house_flat_id = :house_flat_id', ['house_flat_id' => $key->access_to], options: ['singlify']);

                if (!array_key_exists($flats[$key->access_to]['address_house_id'], $houses))
                    $houses[$flats[$key->access_to]['address_house_id']] = $db->get('SELECT house_full FROM addresses_houses WHERE address_house_id = :address_house_id', ['address_house_id' => $flats[$key->access_to]['address_house_id']], options: ['singlify'])['house_full'];

                $key->flat = $flats[$key->access_to]['flat'];

                $key->house_id = $flats[$key->access_to]['address_house_id'];
                $key->house_address = $houses[$flats[$key->access_to]['address_house_id']];
            }
        }

        return self::success(new EntityPage($data, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    public static function index(): array
    {
        return ['GET' => '[Ключи] Получить список'];
    }
}