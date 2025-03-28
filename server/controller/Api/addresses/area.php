<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;
use Selpol\Framework\Http\Response;

readonly class area extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $area = container(AddressFeature::class)->getArea(rule()->onItem('_id', $params));

        return $area ? self::success($area) : self::error('Не удалось найти область', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $areaId = container(AddressFeature::class)->addArea($params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

        return $areaId ? self::success($areaId) : self::error('Не удалось создать область', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->modifyArea($params["_id"], $params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить область', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->deleteArea($params["_id"]);

        return $success ? self::success() : self::error('Не удалось удалить область', 400);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Deprecated] [Адрес] Получить область', 'PUT' => '[Deprecated] [Адрес] Обновить область', 'POST' => '[Deprecated] [Адрес] Создать область', 'DELETE' => '[Deprecated] [Адрес] Удалить область'];
    }
}