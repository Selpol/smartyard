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
            return self::success($suggestions);

        return self::error('Адрес не найден', 404);
    }

    public static function index(): bool|array
    {
        return ["GET" => '[ГеоДанные] Получить список адресов'];
    }
}
