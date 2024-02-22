<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

readonly class house extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $house = container(AddressFeature::class)->getHouse($params["_id"]);

        return $house ? self::success($house) : self::error('Дом не найден', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        if (array_key_exists('magic', $params))
            $houseId = container(AddressFeature::class)->addHouseByMagic($params["magic"]);
        else
            $houseId = container(AddressFeature::class)->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

        return $houseId ? self::success($houseId) : self::error('Не удалось создать дом', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить дом', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->deleteHouse($params["_id"]);

        return $success ? self::success() : self::error('Не удалось удалить дом', 400);
    }

    public static function index(): bool|array
    {
        return ["GET" => '[Дом] Получить список', "PUT" => '[Дом] Обновить дом', "POST" => '[Дом] Создать дом', "DELETE" => '[Дом] Удалить дом'];
    }
}