<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomCmsTask;

class cms extends Api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $cms = $households->getCms($params['_id']);

        return Api::ANSWER($cms, ($cms !== false) ? 'cms' : false);
    }

    public static function PUT(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->setCms($params['_id'], $params['cms']);

        if ($success)
            task(new IntercomCmsTask($params['_id']))->sync();

        return Api::ANSWER($success);
    }

    public static function index(): array
    {
        return ['GET' => '[Дом] Получить КМС', 'PUT' => '[Дом] Обновить КМС'];
    }
}
