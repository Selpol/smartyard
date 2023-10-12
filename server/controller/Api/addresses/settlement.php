<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\api;
use Selpol\Feature\Address\AddressFeature;

class settlement extends api
{
    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifySettlement($params["_id"], $params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $settlementId = container(AddressFeature::class)->addSettlement($params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

        return api::ANSWER($settlementId, ($settlementId !== false) ? "settlementId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteSettlement($params["_id"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): array
    {
        return [
            "PUT" => "[Поселение] Обновить поселение",
            "POST" => "[Поселение] Создать поселение",
            "DELETE" => "[Поселение] Удалить поселение",
        ];
    }
}