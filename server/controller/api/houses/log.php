<?php

namespace api\houses;

use api\api;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Validator\Rule;

class log extends api
{
    public static function GET($params)
    {
        $validate = validator($params, [
            'ip' => [Rule::ipV4()],

            'message' => [Rule::length(max: 64)],

            'minDate' => [Rule::int()],
            'maxDate' => [Rule::int()],

            'size' => [Rule::int(), Rule::min(0), Rule::max(1000)],
            'page' => [Rule::int(), Rule::min(0), Rule::max()]
        ]);

        $logs = container(PlogFeature::class)->getSyslogFilter($validate['ip'], $validate['message'], $validate['minDate'], $validate['maxDate'], $validate['page'], $validate['size']);

        if ($logs)
            return api::SUCCESS('logs', $logs);

        return api::ERROR('Логи не найдены');
    }

    public static function index()
    {
        return ['GET' => '#same(houses,log,GET)'];
    }
}