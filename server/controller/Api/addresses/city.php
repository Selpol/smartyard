<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

readonly class city extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $cityId = container(AddressFeature::class)->addCity($params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

        return $cityId ? self::success($cityId) : self::error('Не удалось создать город', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->modifyCity($params["_id"], $params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить город', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->deleteCity($params["_id"]);

        return $success ? self::success() : self::error('Не удалось удалить город', 400);
    }

    public static function index(): bool|array
    {
        return ['PUT' => '[Город] Обновить город', 'POST' => '[Город] Создать город', 'DELETE' => '[Город] Удалить город'];
    }
}