<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomCmsTask;

class cms extends api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $cms = $households->getCms($params['_id']);

        return api::ANSWER($cms, ($cms !== false) ? 'cms' : false);
    }

    public static function PUT(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->setCms($params['_id'], $params['cms']);

        if ($success)
            task(new IntercomCmsTask($params['_id']))->sync();

        return api::ANSWER($success);
    }

    public static function index(): array
    {
        return ['GET' => '[Дом] Получить КМС', 'PUT' => '[Дом] Обновить КМС'];
    }
}
