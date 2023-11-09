<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Plog\PlogFeature;

readonly class log extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'ip' => rule()->required()->ipV4()->nonNullable(),

            'message' => rule()->string()->max(64),

            'minDate' => rule()->int(),
            'maxDate' => rule()->int(),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $logs = container(PlogFeature::class)->getSyslogFilter($validate['ip'], $validate['message'], $validate['minDate'], $validate['maxDate'], $validate['page'], $validate['size']);

        return self::success($logs ?: []);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Домофон] Получить логи устройства'];
    }
}