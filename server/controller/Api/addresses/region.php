<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

class region extends Api
{
    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifyRegion($params["_id"], $params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $regionId = container(AddressFeature::class)->addRegion($params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

        return Api::ANSWER($regionId, ($regionId !== false) ? "regionId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteRegion($params["_id"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): array
    {
        return [
            "PUT" => "[Регион] Обновить регион",
            "POST" => "[Регион] Создать регион",
            "DELETE" => "[Регион] Удалить регион",
        ];
    }
}