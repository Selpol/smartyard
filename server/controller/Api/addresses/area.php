<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

class area extends Api
{
    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifyArea($params["_id"], $params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $areaId = container(AddressFeature::class)->addArea($params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

        return Api::ANSWER($areaId, ($areaId !== false) ? "areaId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteArea($params["_id"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): bool|array
    {
        return ['PUT' => '[Адрес] Обновить область', 'POST' => '[Адрес] Создать область', 'DELETE' => '[Адрес] Удалить область'];
    }
}