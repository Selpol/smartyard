<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\api;
use Selpol\Feature\Address\AddressFeature;

class house extends api
{
    public static function GET(array $params): array
    {
        $house = container(AddressFeature::class)->getHouse($params["_id"]);

        return api::ANSWER($house, ($house !== false) ? "house" : "notAcceptable");
    }

    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        if (@$params["magic"]) {
            $houseId = container(AddressFeature::class)->addHouseByMagic($params["magic"]);
        } else {
            $houseId = container(AddressFeature::class)->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);
        }

        return api::ANSWER($houseId, ($houseId !== false) ? "houseId" : false);
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteHouse($params["_id"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): bool|array
    {
        return ["GET" => '[Дом] Получить список', "PUT" => '[Дом] Обновить дом', "POST" => '[Дом] Создать дом', "DELETE" => '[Дом] Удалить дом'];
    }
}