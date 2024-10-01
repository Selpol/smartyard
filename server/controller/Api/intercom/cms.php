<?php declare(strict_types=1);

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Framework\Http\Response;

readonly class cms extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $device = intercom(rule()->id()->onItem('_id', $params));

        return self::success(explode(',', $device->resolveString('cms.value', '')));
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Получить список КМС моделей для домофона'];
    }
}