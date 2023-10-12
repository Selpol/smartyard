<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\api;
use Selpol\Feature\Address\AddressFeature;

class street extends api
{
    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifyStreet($params["_id"], $params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $streetId = container(AddressFeature::class)->addStreet($params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

        return api::ANSWER($streetId, ($streetId !== false) ? "streetId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteStreet($params["_id"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): array
    {
        return [
            "PUT" => "[Улица] Обновить улицу",
            "POST" => "[Улица] Создать улицу",
            "DELETE" => "[Улица] Удалить улицу",
        ];
    }
}