<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Framework\Http\Response;

readonly class info extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $intercom = intercom(rule()->id()->onItem('_id', $params));

        if ($intercom)
            return self::success($intercom->getSysInfo());

        return self::error('Не удалось найти устройство', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Получить информацию об устройстве'];
    }
}