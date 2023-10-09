<?php

namespace api\key;

use api\api;
use Selpol\Entity\Repository\House\HouseKeyRepository;
use Selpol\Validator\Rule;

class keys extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'page' => [Rule::int(), Rule::min(0), Rule::max()],
            'size' => [Rule::int(), Rule::min(0), Rule::max(1000)]
        ]);

        return self::SUCCESS('keys', container(HouseKeyRepository::class)->fetchPaginate($validate['page'], $validate['size']));
    }

    public static function index(): array
    {
        return ['GET'];
    }
}