<?php

namespace Selpol\Controller\Mobile;

use PDO;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\Camera\CameraGetRequest;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Block\BlockFeature;
use Psr\Http\Message\ResponseInterface;
use Selpol\Cache\RedisCache;
use Selpol\Controller\Request\Mobile\Camera\CameraCommonDvrRequest;
use Selpol\Controller\Request\Mobile\Camera\CameraEventsRequest;
use Selpol\Controller\Request\Mobile\Camera\CameraIndexRequest;
use Selpol\Controller\Request\Mobile\Camera\CameraShowRequest;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Service\DatabaseService;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

#[Controller('/mobile/cctv')]
readonly class CameraController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/all', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_CCTV]])]
    public function index(CameraIndexRequest $request, HouseFeature $houseFeature, CameraFeature $cameraFeature, DvrFeature $dvrFeature, BlockFeature $blockFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $houses = $this->getHousesWithCameras($user, $request->houseId, $houseFeature, $cameraFeature, $blockFeature);

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

        if (!$camera)
            return user_response(404, message: 'Камера не найдена');

        if (!$camera->checkAccessForSubscriber($this->getUser()->getOriginalValue(), $request->house_id, $request->flat_id, $request->entrance_id))
            return user_response(404, message: 'Доступа к камере нет');

        $response = $camera->toArrayMap([
            'camera_id' => 'id',
            'name' => 'name',
        ]);

        if (!is_null($request->house_id))
            $response['houseId'] = $request->house_id;

        if (!is_null($request->flat_id))
            $response['flatId'] = $request->flat_id;

        if (!is_null($request->entrance_id))
            $response['entranceId'] = $request->entrance_id;

        return user_response(data: $response);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/common', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function common(): ResponseInterface
    {
        $cameras = DeviceCamera::fetchAll(criteria()->equal('common', 1), setting()->columns(['camera_id', 'name', 'lat', 'lon']));

        return user_response(data: $cameras)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
    }

    #[Get('/common/{id}', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function commonDvr(CameraCommonDvrRequest $request, RedisCache $cache): ResponseInterface
    {
        $camera = DeviceCamera::findById($request->id, criteria()->equal('common', 1));

        if (!$camera)
            return user_response(404, message: 'Камера не найдена');

        $dvr = dvr($camera->dvr_server_id);

        if (!$dvr)
            return user_response(404, message: 'Устройство не найден');

        $identifier = $dvr->identifier($camera, $request->time ?? time(), null);

        if (!$identifier)
            return user_response(404, message: 'Идентификатор не найден');

        try {
            $cache->set('dvr:' . $identifier->value, [$identifier->start, $identifier->end, $request->id, null], 360);

            return user_response(data: [
                'identifier' => $identifier,

                'type' => $dvr->server->type,

                'acquire' => $dvr->acquire(null, null),
                'capabilities' => [
                    'poster' => true,
                    'preview' => false,

                    'online' => true,
                    'archive' => false,

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
     * @throws ValidatorException
     */
    #[Get(
        '/{cameraId}',
        includes: [
            FlatMiddleware::class => ['house' => 'houseId'],
            BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM],
            BlockFlatMiddleware::class => ['house' => 'houseId', 'services' => [BlockFeature::SERVICE_INTERCOM]]
        ]
    )]
    public function show(CameraShowRequest $request, int $cameraId, DatabaseService $databaseService, HouseFeature $houseFeature, DvrFeature $dvrFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $camera = DeviceCamera::findById($cameraId);

        if (!$camera)
            return user_response(404, message: 'Камера не найдена');

        $entrances = $houseFeature->getEntrances('houseId', $request->houseId);

        $findEntrance = null;

        foreach ($entrances as $entrance) {
            if ($entrance['cameraId'] == $cameraId) {
                $findEntrance = $entrance;

                break;
            }
        }

        if (!$findEntrance)
            return user_response(404, message: 'Камера не найдена');

        $flats = [];

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] == $request->houseId) {
                $flats[] = $flat['flatId'];
            }
        }

        if (!$flats)
            return user_response(404, message: 'Камера не найдена');

        $statement = $databaseService->getConnection()->prepare('SELECT 1 FROM houses_entrances_flats WHERE house_flat_id IN (' . implode(', ', $flats) . ') AND house_entrance_id = :entrance_id');

        if (!$statement || !$statement->execute(['entrance_id' => $findEntrance['entranceId']]) || $statement->rowCount() == 0 || $statement->fetch(PDO::FETCH_NUM)[0] != 1)
            return user_response(404, message: 'Камера не найдена');

        $dvr = $camera->getDvrServer();

        if (!$dvr)
            return user_response(404, message: 'Камера не найдена');

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
        ]), $user));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/events', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_CCTV],])]
    public function events(CameraEventsRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $domophoneId = $houseFeature->getDomophoneIdByEntranceCameraId($request->cameraId);

        if (is_null($domophoneId))
            return user_response(404, message: 'Домофон не найден');

        $flats = array_filter(
            array_map(static fn(array $item) => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $user['flats']),
            static function (array $flat) use ($houseFeature) {
                $plog = $houseFeature->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == PlogFeature::ACCESS_ALL || $plog == PlogFeature::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0)
            return user_response(404, message: 'Квартира у абонента не найдена');

        $events = $plogFeature->getEventsByFlatsAndDomophone($flatsId, $domophoneId, $request->date);

        if ($events)
            return user_response(200, array_map(static fn(array $item) => $item['date'], $events));

        return user_response(404, message: 'События не найдены');
    }

    private function getHousesWithCameras(array $user, ?int $filterHouseId, HouseFeature $houseFeature, CameraFeature $cameraFeature, BlockFeature $blockFeature): array
    {
        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($filterHouseId != null && $flat['addressHouseId'] != $filterHouseId)
                continue;

            if ($blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_CCTV]) != null)
                continue;

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

            foreach ($flatDetail['entrances'] as $entrance) {
                if (array_key_exists($entrance['entranceId'], $house['doors']))
                    continue;

                $e = $houseFeature->getEntrance($entrance['entranceId']);
                $door = [];

                if ($e['cameraId']) {
                    $cam = $cameraFeature->getCamera($e["cameraId"]);

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
                if ($camera['cameraId'] === null)
                    continue;

                if (array_key_exists($camera['cameraId'], $ids))
                    continue;

                $ids[$camera['cameraId']] = true;

                if (!array_key_exists($camera['dvrServerId'], $dvrs))
                    $dvrs[$camera['dvrServerId']] = DvrServer::findById($camera['dvrServerId'], setting: setting()->nonNullable());

                $result[] = $dvrFeature->convertCameraForSubscriber($dvrs[$camera['dvrServerId']], $camera, $user);
            }
        }

        usort($result, static fn(array $a, array $b) => strcmp($a['name'], $b['name']));

        return $result;
    }
}