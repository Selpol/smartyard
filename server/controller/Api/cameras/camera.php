<?php

namespace Selpol\Controller\Api\cameras;

use Selpol\Controller\Api\api;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\Frs\FrsAddStreamTask;
use Selpol\Task\Tasks\Frs\FrsRemoveStreamTask;

class camera extends api
{
    public static function GET(array $params): array
    {
        $validate = validator($params, ['_id' => rule()->id()]);

        return api::ANSWER(container(CameraFeature::class)->getCamera($validate['_id']));
    }

    public static function POST(array $params): array
    {
        $cameraId = container(CameraFeature::class)->addCamera($params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

        if ($cameraId) {
            if ($params['frs'] && $params['frs'] !== '-')
                task(new FrsAddStreamTask($params['frs'], $cameraId))->high()->dispatch();

            static::modifyIp($cameraId, $params['url']);

            return api::ANSWER($cameraId, 'cameraId');
        }

        return api::ERROR('Камера не добавлена');
    }

    public static function PUT(array $params): array
    {
        $feature = container(CameraFeature::class);

        $camera = $feature->getCamera($params['_id']);

        if ($camera) {
            $success = $feature->modifyCamera($params["_id"], $params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

            if ($success) {
                if ($camera['frs'] !== $params['frs']) {
                    if ($camera['frs'] && $camera['frs'] !== '-')
                        task(new FrsRemoveStreamTask($camera['frs'], $camera['cameraId']))->high()->dispatch();

                    if ($params['frs'] && $params['frs'] !== '-')
                        task(new FrsAddStreamTask($params['frs'], $camera['cameraId']))->high()->dispatch();
                }

                if ($camera['url'] !== $params['url'])
                    static::modifyIp($camera['cameraId'], $params['url']);
            }

            return api::ANSWER($success ?: $params["_id"], $success ? "cameraId" : false);
        }

        return api::ERROR('Камера не найдена');
    }

    public static function DELETE(array $params): array
    {
        $feature = container(CameraFeature::class);

        $camera = $feature->getCamera($params['_id']);

        if ($camera) {
            $success = $feature->deleteCamera($params["_id"]);

            if ($success && $camera['frs'] && $camera['frs'] !== '-')
                task(new FrsRemoveStreamTask($camera['frs'], $camera['cameraId']))->high()->dispatch();

            return api::ANSWER($success);
        }

        return api::ERROR('Камера не найдена');
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

    private static function modifyIp(int $id, string $url): void
    {
        $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            container(DatabaseService::class)->modify('UPDATE cameras SET ip = :ip WHERE camera_id = :id', ['ip' => $ip, 'id' => $id]);
    }
}
