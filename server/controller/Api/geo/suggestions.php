<?php

namespace Selpol\Controller\Api\geo;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Geo\GeoFeature;

class suggestions extends Api
{

    public static function GET(array $params): array
    {
        $suggestions = container(GeoFeature::class)->suggestions($params["search"]);

        return Api::ANSWER($suggestions, ($suggestions !== false) ? "suggestions" : "404");
    }

    public static function index(): bool|array
    {
        return ["GET" => '[ГеоДанные] Получить список адресов'];
    }
}
