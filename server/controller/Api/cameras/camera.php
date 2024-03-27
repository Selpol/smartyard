<?php

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Frs\FrsAddStreamTask;
use Selpol\Task\Tasks\Frs\FrsRemoveStreamTask;

readonly class camera extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $criteria = criteria();

        if (!container(AuthService::class)->checkScope('camera-hidden'))
            $criteria->equal('hidden', false);

        return self::success(DeviceCamera::findById($params['_id'], $criteria, setting()->nonNullable())->toArrayMap([
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
            "comment" => "comment",
            "hidden" => "hidden"
        ]));
    }

    public static function POST(array $params): ResponseInterface
    {
        $camera = new DeviceCamera();

        self::set($camera, $params);

        if ($camera->insert()) {
            if ($camera->frs_server_id)
                task(new FrsAddStreamTask($camera->frs_server_id, $camera->camera_id))->high()->dispatch();

            return self::success($camera->camera_id);
        }

        return self::error('Не удалось создать камеру', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $camera = DeviceCamera::findById($params['_id'], setting: setting()->nonNullable());

        self::set($camera, $params);

        if ($camera->update()) {
            if (array_key_exists('frs_server_id', $params)) {
                if ($camera->frs_server_id !== $params['frs_server_id'])
                    task(new FrsRemoveStreamTask($camera->frs_server_id, $camera->camera_id))->high()->dispatch();

                if ($params['frs_server_id'])
                    task(new FrsAddStreamTask($params['frs_server_id'], $camera->camera_id))->high()->dispatch();
            }

            return self::success($camera->camera_id);
        }

        return self::error('Не удалось обновить камеру', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $camera = DeviceCamera::findById($params['_id'], setting: setting()->nonNullable());

        if ($camera->delete()) {
            if ($camera->frs_server_id)
                task(new FrsRemoveStreamTask($camera->frs_server_id, $camera->camera_id))->high()->dispatch();

            return self::success();
        }

        return self::error('Не удалось удалить камеру', 400);
    }

    public static function index(): bool|array
    {
        return [
            'GET' => '[Камера] Получить камеру',
            'PUT' => '[Камера] Обновить камеру',
            'POST' => '[Камера] Создать камеру',
            'DELETE' => '[Камера] Удалить камеру'
        ];
    }

    private static function set(DeviceCamera $camera, array $params): void
    {
        $camera->enabled = $params['enabled'];

        if (array_key_exists('dvr_server_id', $params))
            $camera->dvr_server_id = $params['dvr_server_id'];

        if (array_key_exists('frs_server_id', $params))
            $camera->frs_server_id = $params['frs_server_id'];

        $camera->model = $params['model'];
        $camera->url = $params['url'];
        $camera->stream = $params['stream'];
        $camera->credentials = $params['credentials'];
        $camera->name = $params['name'];
        $camera->dvr_stream = $params['dvrStream'];
        $camera->timezone = $params['timezone'];

        $camera->lat = $params['lat'];
        $camera->lon = $params['lon'];

        $camera->direction = $params['direction'];
        $camera->angle = $params['angle'];
        $camera->distance = $params['distance'];

        $camera->md_left = $params['mdLeft'];
        $camera->md_top = $params['mdTop'];
        $camera->md_width = $params['mdWidth'];
        $camera->md_height = $params['mdHeight'];

        $camera->common = $params['common'];

        $camera->comment = $params['comment'];

        if (array_key_exists('hidden', $params))
            $camera->hidden = $params['hidden'];

        $ip = gethostbyname(parse_url($camera->url, PHP_URL_HOST));

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            $camera->ip = $ip;
    }
}