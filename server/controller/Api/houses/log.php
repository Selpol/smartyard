<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;;
use Selpol\Feature\Plog\PlogFeature;

class log extends Api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, [
            'ip' => rule()->required()->ipV4()->nonNullable(),

            'message' => rule()->string()->max(64),

            'minDate' => rule()->int(),
            'maxDate' => rule()->int(),

            'page' => rule()->int()->clamp(0),
            'size' => rule()->int()->clamp(1, 1000)
        ]);

        $logs = container(PlogFeature::class)->getSyslogFilter($validate['ip'], $validate['message'], $validate['minDate'], $validate['maxDate'], $validate['page'], $validate['size']);

        if ($logs)
            return Api::SUCCESS('logs', $logs);

        return Api::SUCCESS('logs', []);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Дом] Получить логи устройства'];
    }
}