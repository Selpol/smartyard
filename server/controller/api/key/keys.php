<?php

namespace api\key;

use api\api;
use Selpol\Entity\Repository\House\HouseKeyRepository;
use Selpol\Service\Database\Page;
use Selpol\Service\DatabaseService;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;

class keys extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'rfid' => [Rule::length()],
            'comments' => [Rule::length()],

            'page' => [Filter::default(0), Rule::int(), Rule::min(0), Rule::max()],
            'size' => [Filter::default(10), Rule::int(), Rule::min(0), Rule::max(1000)]
        ]);

        $page = container(HouseKeyRepository::class)->fetchPaginate($validate['page'], $validate['size'], criteria()->like('rfid', $validate['rfid'])->orLike('comments', $validate['comments'])->asc('house_rfid_id')->asc('access_to'));
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

        return self::SUCCESS('keys', new Page($data, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    public static function index(): array
    {
        return ['GET' => '[Ключи] Получить список'];
    }
}