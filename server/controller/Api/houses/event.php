<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\House\HouseFlatRepository;
use Selpol\Feature\Plog\PlogFeature;

class event extends Api
{
    public static function GET(array $params): array
    {
        $id = rule()->id()->onItem('_id', $params);

        $flat = container(HouseFlatRepository::class)->findById($id);

        $validate = validator($params, [
            'type' => rule()->int(),
            'opened' => rule()->bool(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $result = container(PlogFeature::class)->getEventsByFlat($flat->house_flat_id, $validate['type'], $validate['opened'], $validate['page'], $validate['size']);

        if ($result)
            return self::SUCCESS('events', $result);

        return self::SUCCESS('events', []);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Дом] Получить список событий'];
    }
}