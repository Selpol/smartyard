<?php declare(strict_types=1);

namespace Selpol\Controller\Api\dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Framework\Http\Response;

readonly class cameras extends Api
{
    public static function GET(array $params): array|Response|ResponseInterface
    {
        $id = rule()->id()->onItem('_id', $params);

        if ($cameras = dvr($id)?->getCameras())
            return self::success($cameras);

        return self::success([]);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Dvr] Получить список камер на сервере'];
    }
}