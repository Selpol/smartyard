<?php

namespace Selpol\Controller\Api\addresses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

readonly class street extends Api
{
    public static function PUT(array $params): array
    {
        $success = container(AddressFeature::class)->modifyStreet($params["_id"], $params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $streetId = container(AddressFeature::class)->addStreet($params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

        return Api::ANSWER($streetId, ($streetId !== false) ? "streetId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $success = container(AddressFeature::class)->deleteStreet($params["_id"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
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