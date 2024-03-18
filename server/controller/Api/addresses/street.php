<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;
use Selpol\Framework\Http\Response;

readonly class street extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $street = container(AddressFeature::class)->getStreet(rule()->onItem('_id', $params));

        return $street ? self::success($street) : self::error('Не удалось найти улицу', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $streetId = container(AddressFeature::class)->addStreet($params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

        return $streetId ? self::success($streetId) : self::error('Не удалось создать улицу', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->modifyStreet($params["_id"], $params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить улицу', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $success = container(AddressFeature::class)->deleteStreet($params["_id"]);

        return $success ? self::success() : self::error('Не удалось удалить улицу', 400);
    }

    public static function index(): array
    {
        return [
            'GET' => '[Улица] Получить улицу',
            "PUT" => "[Улица] Обновить улицу",
            "POST" => "[Улица] Создать улицу",
            "DELETE" => "[Улица] Удалить улицу",
        ];
    }
}