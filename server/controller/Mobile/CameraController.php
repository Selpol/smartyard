<?php

namespace Selpol\Controller\Mobile;

use Selpol\Device\Ip\Dvr\DvrDevice;
use Selpol\Device\Ip\Dvr\Common\DvrIdentifier;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\Camera\CameraGetRequest;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Block\BlockFeature;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Request\Mobile\Camera\CameraCommonDvrRequest;
use Selpol\Controller\Request\Mobile\Camera\CameraEventsRequest;
use Selpol\Controller\Request\Mobile\Camera\CameraIndexRequest;
use Selpol\Controller\Request\Mobile\Camera\CameraShowRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Service\RedisService;
use Throwable;

#[Controller('/mobile/cctv')]
readonly class CameraController extends MobileRbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/all', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_CCTV]])]
    public function index(CameraIndexRequest $request, HouseFeature $houseFeature, DvrFeature $dvrFeature, BlockFeature $blockFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $houses = $this->getHousesWithCameras($user, $request->houseId, $houseFeature, $blockFeature);

        return user_response(200, $this->convertCameras($houses, $dvrFeature, $user));
    }

    #[Get(
        '/show/{id}',
        includes: [
            FlatMiddleware::class => ['flat' => 'flat_id', 'house' => 'house_id'],
            BlockMiddleware::class => [BlockFeature::SERVICE_CCTV],
            BlockFlatMiddleware::class => ['flat' => 'flat_id', 'house' => 'house_id', 'services' => [BlockFeature::SERVICE_CCTV]]
        ]
    )]
    public function get(CameraGetRequest $request, int $id): ResponseInterface
    {
        $camera = DeviceCamera::findById($id, setting: setting()->columns(['camera_id', 'name']));

        if (!$camera instanceof DeviceCamera) {
            return user_response(404, message: 'Камера не найдена');
        }

        if (!$camera->checkAccessForSubscriber($this->getUser()->getOriginalValue(), $request->house_id, $request->flat_id, $request->entrance_id)) {
            return user_response(404, message: 'Доступа к камере нет');
        }

        $response = $camera->toArrayMap([
            'camera_id' => 'id',
            'name' => 'name',
        ]);

        if (!is_null($request->house_id)) {
            $response['houseId'] = $request->house_id;
        }

        if (!is_null($request->flat_id)) {
            $response['flatId'] = $request->flat_id;
        }

        if (!is_null($request->entrance_id)) {
            $response['entranceId'] = $request->entrance_id;
        }

        return user_response(data: $response);
    }

    #[Get('/preview/{id}')]
    public function preview(int $id, RedisService $service): ResponseInterface
    {
        $camera = DeviceCamera::findById($id);

        if (!$camera instanceof DeviceCamera) {
            return user_response(404, message: 'Камера не найдена');
        }

        $dvr = dvr($camera->dvr_server_id);

        if (!$dvr instanceof DvrDevice) {
            return user_response(404, message: 'Получить скриншот не возможно');
        }

        $time = time();
        $time = $time - $time % 86400 + 43200;

        $identifier = $dvr->identifier($camera, $time, $this->getUser()->getIdentifier());

        $screenshot = $service->useCacheEx(1, 'camera:' . $id . ':preview', 7 * 86400, function () use ($camera, $identifier, $time, $dvr, $id) {
            $value = $dvr->screenshot($identifier, $camera, $time);

            if ($value) {
                return $value->getContents();
            }

            throw new KernelException(message: 'Не удалось получить скриншот', code: 404);
        });

        return response(
            headers: [
                'Content-Type' => ['image/jpeg'],
                'Expires' => [gmdate('D, d M Y H:i:s \G\M\T', time() + 86400)]
            ]
        )->withBody(stream($screenshot));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/common', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function common(): ResponseInterface
    {
        $cameras = DeviceCamera::fetchAll(criteria()->equal('common', 1), setting()->columns(['camera_id', 'name', 'lat', 'lon']));

        return user_response(data: array_map(static fn(DeviceCamera $camera): array => $camera->toArrayMap(['camera_id' => 'id', 'name' => 'name', 'lat' => 'lat', 'lon' => 'lon']), $cameras))
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
    }

    #[Get('/common/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function commonDvr(CameraCommonDvrRequest $request): ResponseInterface
    {
        $camera = DeviceCamera::findById($request->id, criteria()->equal('common', 1));

        if (!$camera instanceof DeviceCamera) {
            return user_response(404, message: 'Камера не найдена');
        }

        $dvr = dvr($camera->dvr_server_id);

        if (!$dvr instanceof DvrDevice) {
            return user_response(404, message: 'Устройство не найден');
        }

        $identifier = $dvr->identifier($camera, $request->time ?? time(), null);

        if (!$identifier instanceof DvrIdentifier) {
            return user_response(404, message: 'Идентификатор не найден');
        }

        try {
            return user_response(data: [
                'identifier' => ['value' => $identifier->toToken(), 'start' => $identifier->start, 'end' => $identifier->end],

                'type' => $dvr->server->type,

                'capabilities' => [
                    'poster' => true,
                    'preview' => false,

                    'online' => true,
                    'archive' => false,

                    'command' => [],
                    'speed' => []
                ]
            ]);
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get(
        '/{id}',
        includes: [
            FlatMiddleware::class => ['house' => 'houseId'],
            BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM],
            BlockFlatMiddleware::class => ['house' => 'houseId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function show(CameraShowRequest $request, DvrFeature $dvrFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $camera = DeviceCamera::findById($request->id);

        if (!$camera instanceof DeviceCamera) {
            return user_response(404, message: 'Камера не найдена');
        }

        $dvr = $camera->getDvrServer();

        if (!$dvr instanceof DvrServer) {
            return user_response(404, message: 'Камера пустая');
        }

        return user_response(data: $dvrFeature->convertCameraForSubscriber($dvr, $camera->toArrayMap([
            "camera_id" => "cameraId",
            "dvr_server_id" => "dvrServerId",
            "frs_server_id" => "frsServerId",
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
            "frs" => "frs",
            "common" => "common",
            "comment" => "comment"
        ]), $user));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/events', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_CCTV]])]
    public function events(CameraEventsRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $domophoneId = $houseFeature->getDomophoneIdByEntranceCameraId($request->cameraId);

        if (is_null($domophoneId)) {
            return user_response(404, message: 'Домофон не найден');
        }

        $flats = array_filter(
            array_map(static fn(array $item): array => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $user['flats']),
            static function (array $flat) use ($houseFeature): bool {
                $plog = $houseFeature->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == PlogFeature::ACCESS_ALL || $plog == PlogFeature::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0) {
            return user_response(404, message: 'Квартира у абонента не найдена');
        }

        $events = $plogFeature->getEventsByFlatsAndDomophone($flatsId, $domophoneId, $request->date);

        if ($events) {
            return user_response(200, array_map(static fn(array $item) => $item['date'], $events));
        }

        return user_response(404, message: 'События не найдены');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/motions', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_CCTV]])]
    public function motions(CameraEventsRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $domophoneId = $houseFeature->getDomophoneIdByEntranceCameraId($request->cameraId);

        if (is_null($domophoneId)) {
            return user_response(404, message: 'Домофон не найден');
        }

        $flats = array_filter(
            array_map(static fn(array $item): array => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $user['flats']),
            static function (array $flat) use ($houseFeature): bool {
                $plog = $houseFeature->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == PlogFeature::ACCESS_ALL || $plog == PlogFeature::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0) {
            return user_response(404, message: 'Квартира у абонента не найдена');
        }

        $intercom = DeviceIntercom::findById($domophoneId, setting: setting()->columns(['ip']));

        if ($intercom == null) {
            return user_response(404, message: 'Домофон не найден');
        }

        $motions = $plogFeature->getMotionsByHost($intercom->ip, $request->date);

        if ($motions) {
            return user_response(200, array_map(static fn(array $item) => [$item['start'], $item['end']], $motions));
        }

        return user_response(404, message: 'Движения не найдены');
    }

    private function getHousesWithCameras(array $user, ?int $filterHouseId, HouseFeature $houseFeature, BlockFeature $blockFeature): array
    {
        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($filterHouseId != null && $flat['addressHouseId'] != $filterHouseId) {
                continue;
            }

            if ($blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_CCTV]) != null) {
                continue;
            }

            $flatDetail = $houseFeature->getFlat($flat['flatId']);

            $houseId = $flat['addressHouseId'];

            if (array_key_exists($houseId, $houses)) {
                $house = &$houses[$houseId];
            } else {
                $houses[$houseId] = [];
                $house = &$houses[$houseId];
                $house['houseId'] = strval($houseId);

                $house['cameras'] = array_map(static function (array $camera) use ($houseId) {
                    $camera['houseId'] = $houseId;

                    return $camera;
                }, $houseFeature->getCameras("houseId", $houseId));
                $house['doors'] = [];
            }

            $flatCameras = $houseFeature->getCameras("flatId", $flat['flatId']);

            $house['cameras'] = array_merge($house['cameras'], array_map(static function (array $camera) use ($flat) {
                $camera['flatId'] = $flat['flatId'];

                return $camera;
            }, $flatCameras));

            if ($blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SUB_SERVICE_INTERCOM]) != null) {
                continue;
            }

            foreach ($flatDetail['entrances'] as $entrance) {
                if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                    continue;
                }

                $e = $houseFeature->getEntrance($entrance['entranceId']);
                $door = [];

                if ($e['cameraId']) {
                    $cam = DeviceCamera::findById($e['cameraId'], setting: setting()->nonNullable())->toOldArray();

                    $cam['entranceId'] = $entrance['entranceId'];
                    $cam['houseId'] = $houseId;
                    $cam['flatId'] = $flat['flatId'];

                    $house['cameras'][] = $cam;
                }

                $house['doors'][$entrance['entranceId']] = $door;
            }
        }

        return $houses;
    }

    private function convertCameras(array $houses, DvrFeature $dvrFeature, array $user): array
    {
        $ids = [];
        $result = [];

        /** @var array<int, DvrServer> $dvrs */
        $dvrs = [];

        foreach ($houses as $house_key => $h) {
            $houses[$house_key]['doors'] = array_values($h['doors']);

            unset($houses[$house_key]['cameras']);

            foreach ($h['cameras'] as $camera) {
                if ($camera['cameraId'] === null) {
                    continue;
                }

                if (array_key_exists($camera['cameraId'], $ids)) {
                    continue;
                }

                $ids[$camera['cameraId']] = true;

                if (!array_key_exists($camera['dvrServerId'], $dvrs)) {
                    $dvrs[$camera['dvrServerId']] = DvrServer::findById($camera['dvrServerId'], setting: setting()->nonNullable());
                }

                $result[] = $dvrFeature->convertCameraForSubscriber($dvrs[$camera['dvrServerId']], $camera, $user);
            }
        }

        usort($result, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $result;
    }
}