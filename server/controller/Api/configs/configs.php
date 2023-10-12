<?php

namespace Selpol\Controller\Api\configs;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Frs\FrsFeature;

class configs extends Api
{
    public static function GET(array $params): array
    {
        $sections = ["FRSServers" => container(FrsFeature::class)->servers()];

        return Api::ANSWER($sections, "sections");
    }

    public static function index(): bool|array
    {
        return [
            "GET" => "[Настройка] Получить список настроек",
        ];
    }
}
