<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

readonly class settlement extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $settlementId = container(AddressFeature::class)->addSettlement($params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

        return $settlementId ? self::success($settlementId) : self::error('Не удалось удалить поселение', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->modifySettlement($params["_id"], $params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить поселение', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->deleteSettlement($params["_id"]);

        return $success ? self::success() : self::error('Не удалось удалить поселение', 400);
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