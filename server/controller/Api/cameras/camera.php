<?php

namespace Selpol\Controller\Api\cameras;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository\Device\DeviceCameraRepository;
use Selpol\Task\Tasks\Frs\FrsAddStreamTask;
use Selpol\Task\Tasks\Frs\FrsRemoveStreamTask;

class camera extends Api
{
    public static function GET(array $params): array
    {
        return self::ANSWER(container(DeviceCameraRepository::class)->findById($params['_id'])->toArrayMap([
            "camera_id" => "cameraId",
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
            "frs" => "frs",
            "md_left" => "mdLeft",
            "md_top" => "mdTop",
            "md_width" => "mdWidth",
            "md_height" => "mdHeight",
            "common" => "common",
            "comment" => "comment"
        ]));
    }

    public static function POST(array $params): array
    {
        $camera = new DeviceCamera();

        self::set($camera, $params);

        if (container(DeviceCameraRepository::class)->insert($camera)) {
            if ($camera->frs && $camera->frs !== '-')
                task(new FrsAddStreamTask($camera->frs, $camera->camera_id))->high()->dispatch();

            return self::ANSWER($camera->camera_id, 'cameraId');
        }

        return self::ERROR('Неудалось добавить камеру');
    }

    public static function PUT(array $params): array
    {
        $camera = container(DeviceCameraRepository::class)->findById($params['_id']);

        self::set($camera, $params);

        if (container(DeviceCameraRepository::class)->update($camera)) {
            if ($camera->frs !== $params['frs']) {
                if ($camera->frs && $camera->frs !== '-')
                    task(new FrsRemoveStreamTask($camera->frs, $camera->camera_id))->high()->dispatch();

                if ($params['frs'] && $params['frs'] !== '-')
                    task(new FrsAddStreamTask($params['frs'], $camera->camera_id))->high()->dispatch();
            }

            return self::ANSWER($camera->camera_id, 'cameraId');
        }

        return Api::ERROR('Неудалось обновить камеру');
    }

    public static function DELETE(array $params): array
    {
        $camera = container(DeviceCameraRepository::class)->findById($params['_id']);

        if (container(DeviceCameraRepository::class)->delete($camera)) {
            if ($camera->frs && $camera->frs !== '-')
                task(new FrsRemoveStreamTask($camera->frs, $camera->camera_id))->high()->dispatch();

            return self::ANSWER();
        }

        return Api::ERROR('Неудалось удалить камеру');
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

    private static function set(DeviceCamera $camera, array $params)
    {
        $camera->enabled = $params['enabled'];

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

        $camera->frs = $params['frs'];

        $camera->md_left = $params['mdLeft'];
        $camera->md_top = $params['mdTop'];
        $camera->md_width = $params['mdWidth'];
        $camera->md_height = $params['mdHeight'];

        $camera->common = $params['common'];

        $camera->comment = $params['comment'];

        $ip = gethostbyname(parse_url($camera->url, PHP_URL_HOST));

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            $camera->ip = $ip;
    }
}