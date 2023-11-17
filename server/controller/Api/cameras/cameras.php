<?php

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Entity\EntityPage;

readonly class cameras extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'comment' => rule()->string()->clamp(0, 1000),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $page = DeviceCamera::fetchPage($validate['page'], $validate['size'], criteria()->like('comment', $validate['comment'])->asc('camera_id'));

        $result = [];

        foreach ($page->getData() as $data)
            $result[] = $data->toArrayMap([
                "camera_id" => "cameraId",
                "dvr_server_id" => "dvr_server_id",
                "frs_server_id" => "frs_server_id",
                "enabled" => "enabled",
                "model" => "model",
                "url" => "url",
                "stream" => "stream",
                "credentials" => "credentials",
                "name" => "name",
                "dvr_stream" => "dvrStream",
                "timezone" => "timezone",
                "lat" => "lat",
                "lon" => "lon",
                "direction" => "direction",
                "angle" => "angle",
                "distance" => "distance",
                "md_left" => "mdLeft",
                "md_top" => "mdTop",
                "md_width" => "mdWidth",
                "md_height" => "mdHeight",
                "common" => "common",
                "comment" => "comment"
            ]);

        return self::success(new EntityPage($result, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Камера] Получить список'];
    }
}