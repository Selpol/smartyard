<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;

readonly class intercoms extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        return self::success(DeviceIntercom::fetchPage($validate['page'], $validate['size'], criteria()->asc('id')));
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Домофон] Получить список домофонов'];
    }
}