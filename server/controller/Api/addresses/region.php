<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

readonly class region extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $regionId = container(AddressFeature::class)->addRegion($params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

        return $regionId ? self::success($regionId) : self::error('Не удалось создать регион', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->modifyRegion($params["_id"], $params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить регион', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->deleteRegion($params["_id"]);

        return $success ? self::success() : self::error('Не удалось удалить регион', 400);
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