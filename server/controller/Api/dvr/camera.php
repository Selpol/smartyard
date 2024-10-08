<?php

namespace Selpol\Controller\Api\dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class camera extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'query' => rule()->required()->string()->nonNullable()
        ]);

        if (($id = dvr($validate['_id'])?->getCameraId($validate['query'])) !== null && ($id = dvr($validate['_id'])?->getCameraId($validate['query'])) !== '' && ($id = dvr($validate['_id'])?->getCameraId($validate['query'])) !== '0') {
            return self::success($id);
        }

        return self::error('Камера не найдена', 404);
    }

    public static function index(): array|bool
    {
        return ['POST' => '[Dvr] Найти идентификатор камеры'];
    }
}