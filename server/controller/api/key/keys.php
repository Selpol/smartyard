<?php

namespace api\key;

use api\api;
use Selpol\Entity\Repository\House\HouseKeyRepository;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;

class keys extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'page' => [Filter::default(0), Rule::int(), Rule::min(0), Rule::max()],
            'size' => [Filter::default(10), Rule::int(), Rule::min(0), Rule::max(1000)]
        ]);

        return self::SUCCESS('keys', container(HouseKeyRepository::class)->fetchPaginate($validate['page'], $validate['size']));
    }

    public static function index(): array
    {
        return ['GET' => '[Ключи] Получить список'];
    }
}