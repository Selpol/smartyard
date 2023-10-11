<?php

namespace api\houses;

use api\api;
use Selpol\Feature\Plog\PlogFeature;

class log extends api
{
    public static function GET($params)
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
            return api::SUCCESS('logs', $logs);

        return api::SUCCESS('logs', []);
    }

    public static function index()
    {
        return ['GET' => '[Дом] Получить логи устройства'];
    }
}