<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\Plog\PlogFeature;

readonly class event extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $flat = HouseFlat::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        $validate = validator($params, [
            'type' => rule()->int(),
            'opened' => rule()->bool(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $result = container(PlogFeature::class)->getEventsByFlat($flat->house_flat_id, $validate['type'], $validate['opened'], $validate['page'], $validate['size']);

        return self::success($result ?: []);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Дом] Получить список событий'];
    }
}