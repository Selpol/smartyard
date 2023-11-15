<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSyncCmsTask;

readonly class cms extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $cms = $households->getCms($params['_id']);

        return $cms ? self::success($cms) : self::success([]);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->setCms($params['_id'], $params['cms']);

        if ($success)
            task(new IntercomSyncCmsTask($params['_id']))->high()->dispatch();

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить КМС', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Дом] Получить КМС', 'PUT' => '[Дом] Обновить КМС'];
    }
}