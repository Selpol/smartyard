<?php

declare(strict_types=1);

namespace Selpol\Controller\Admin\Dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\DvrImportRequest;
use Selpol\Controller\Request\Admin\DvrShowRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Управление DVR серверами
 */
#[Controller('/admin/dvr')]
readonly class DvrController extends AdminRbtController
{
    /**
     * Получить камеры с DVR сервера
     *
     * @param int $id Идентификатор DVR сервера
     */
    #[Get('/{id}')]
    public function index(int $id): ResponseInterface
    {
        $cameras = dvr($id)?->getCameras();

        if ($cameras !== null && $cameras !== []) {
            usort($cameras, self::sort(...));

            return self::success($cameras);
        }

        return self::success([]);
    }

    /**
     * Получить камеру с сервера
     */
    #[Get('/show/{id}')]
    public function show(DvrShowRequest $request): ResponseInterface
    {
        $camera = dvr($request->id)?->getCamera($request->camera);

        if ($camera) {
            return self::success($camera);
        }

        return self::error('Камера не найдена', 404);
    }

    /**
     * Импортирование камер с сервера
     */
    #[Get('/import/{id}')]
    public function import(DvrImportRequest $request): ResponseInterface
    {
        $ids = [];

        $dvr = dvr($request->id);

        foreach ($request->cameras as $id) {
            $dvrCamera = $dvr->getCamera($id);

            if (!$dvrCamera) {
                continue;
            }

            $camera = new DeviceCamera();

            $camera->dvr_server_id = $request->id;
            $camera->frs_server_id = $request->frs_server_id;

            $camera->enabled = 1;

            $camera->model = $request->model;

            $camera->url = 'http://' . $dvrCamera->ip;
            $camera->stream = $dvrCamera->url;
            $camera->credentials = $dvrCamera->password;
            $camera->name = $dvrCamera->title;
            $camera->dvr_stream = $id;
            $camera->timezone = 'Europe/Moscow';

            $camera->common = 0;

            $camera->ip = $dvrCamera->ip;

            $camera->comment = $dvrCamera->title;

            if (!$camera->safeInsert()) {
                continue;
            }

            $ids[$id] = $camera->camera_id;
        }

        return self::success($ids);
    }

    private static function sort(array $a, array $b): int
    {
        return strcmp($a['title'], $b['title']);
    }
}
