<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\api;
use Selpol\Feature\Address\AddressFeature;

class city extends api
{
    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifyCity($params["_id"], $params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $cityId = container(AddressFeature::class)->addCity($params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

        return api::ANSWER($cityId, ($cityId !== false) ? "cityId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteCity($params["_id"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): bool|array
    {
        return ['PUT' => '[Город] Обновить город', 'POST' => '[Город] Создать город', 'DELETE' => '[Город] Удалить город'];
    }
}