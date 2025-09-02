<?php

declare(strict_types=1);

namespace Selpol\Controller\Admin\Dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\DvrImportRequest;
use Selpol\Controller\Request\Admin\DvrShowRequest;
use Selpol\Device\Ip\Dvr\DvrCamera;
use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Framework\Entity\Database\EntityConnectionInterface;
use Selpol\Framework\Entity\Exception\EntityException;
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
    public function import(DvrImportRequest $request, EntityConnectionInterface $connection): ResponseInterface
    {
        $ids = [];

        $dvr = dvr($request->id);
        $cameras = self::getCameras($dvr, $request->cameras);

        $house = $request->address_house_id ? AddressHouse::findById($request->address_house_id) : null;

        $connection->beginTransaction();

        foreach ($cameras as $dvrCamera) {
            $camera = new DeviceCamera();

            $camera->dvr_server_id = $request->id;
            $camera->frs_server_id = $request->frs_server_id;

            $camera->enabled = 1;

            $camera->model = $request->model;

            $camera->url = 'http://' . $dvrCamera->ip;
            $camera->stream = $dvrCamera->url;
            $camera->credentials = $dvrCamera->password;
            $camera->name = $dvrCamera->title;
            $camera->dvr_stream = $dvrCamera->id;
            $camera->timezone = 'Europe/Moscow';

            $camera->common = 0;

            if (!is_null($request->lat) && !is_null($request->lon)) {
                $camera->lat = $request->lat;
                $camera->lon = $request->lon;
            } else {
                $position = config('position', [0, 0]);

                $camera->lat = $position[0];
                $camera->lon = $position[1];
            }

            $camera->ip = $dvrCamera->ip;

            $camera->comment = $dvrCamera->title;

            try {
                $camera->insert();
            } catch (EntityException $exception) {
                foreach ($exception->getMessages() as $message) {
                    if ($message->code != 23505) {
                        $connection->rollBack();

                        return self::error($message->message);
                    }
                }

                continue;
            }

            $ids[$dvrCamera->id] = $camera->camera_id;

            if ($house) {
                $house->cameras()->add($camera);
            }
        }

        $connection->commit();

        return self::success($ids);
    }

    /**
     * Summary of getCameras
     * @param \Selpol\Device\Ip\Dvr\DvrDevice $device
     * @param string[] $names
     * @return \Selpol\Device\Ip\Dvr\DvrCamera[]
     */
    private static function getCameras(DvrDevice $device, array $names): array
    {
        if ($device->model->isTrassir()) {
            $ip = gethostbyname(parse_url($device->server->url, PHP_URL_HOST));

            return array_reduce($device->getCameras(), static function (array $previous, array $current) use ($device, $ip): array {
                $previous[] = new DvrCamera($current['id'], $current['title'], $device->server->url, $ip, '', '');

                return $previous;
            }, []);
        } else {
            return array_reduce($names, static function (array $previous, string $current) use ($device): array {
                $previous[] = $device->getCamera($current);

                return $previous;
            }, []);
        }
    }

    private static function sort(array $a, array $b): int
    {
        return strcmp($a['title'], $b['title']);
    }
}
