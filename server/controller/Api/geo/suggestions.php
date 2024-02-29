<?php

namespace Selpol\Controller\Api\geo;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Geo\GeoFeature;

readonly class suggestions extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $suggestions = container(GeoFeature::class)->suggestions($params["search"], array_key_exists('bound', $params) ? $params['bound'] : null);

        if ($suggestions)
            return self::success(array_map(static function (array $suggestion) {
                return [
                    'value' => $suggestion['value'],

                    'latitude' => array_key_exists('geo_lat', $suggestion['data']) ? $suggestion['data']['geo_lat'] : null,
                    'longitude' => array_key_exists('geo_lon', $suggestion['data']) ? $suggestion['data']['geo_lon'] : null,

                    'data' => [
                        'region_fias_id' => $suggestion['data']['region_fias_id'],
                        'area_fias_id' => $suggestion['data']['area_fias_id'],
                        'city_fias_id' => $suggestion['data']['city_fias_id'],
                        'settlement_fias_id' => $suggestion['data']['settlement_fias_id'],
                        'street_fias_id' => $suggestion['data']['street_fias_id'],
                        'house_fias_id' => $suggestion['data']['house_fias_id'],

                        'fias_level' => $suggestion['data']['fias_level']
                    ]
                ];
            }, $suggestions));

        return self::error('Адрес не найден', 404);
    }

    public static function index(): bool|array
    {
        return ["GET" => '[ГеоДанные] Получить список адресов'];
    }
}
