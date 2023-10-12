<?php

namespace Selpol\Controller\Api\configs;

use Selpol\Controller\Api\api;
use Selpol\Feature\Frs\FrsFeature;

class configs extends api
{
    public static function GET(array $params): array
    {
        $sections = ["FRSServers" => container(FrsFeature::class)->servers()];

        return api::ANSWER($sections, "sections");
    }

    public static function index(): bool|array
    {
        return [
            "GET" => "[Настройка] Получить список настроек",
        ];
    }
}
