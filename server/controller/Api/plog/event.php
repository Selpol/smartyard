<?php

namespace Selpol\Controller\Api\plog;

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

        if ($result)
            return self::success(array_map(static function (array $item) {
                if (array_key_exists('phones', $item) && is_array($item['phones']))
                    if (array_key_exists('user_phone', $item['phones']) && $item['phones']['user_phone'])
                        $item['phones']['user_phone'] = mobile_mask($item['phones']['user_phone']);

                return $item;
            }, $result));

        return self::success([]);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[События] Получить список'];
    }
}