<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Selpol\Cache\RedisCache;
use Selpol\Controller\RbtController;
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
use Selpol\Middleware\Mobile\AuthMiddleware;
use Selpol\Middleware\Mobile\SubscriberMiddleware;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

#[Controller('/mobile/cctv')]
readonly class CameraController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/all')]
    public function index(CameraIndexRequest $request, HouseFeature $houseFeature, CameraFeature $cameraFeature, DvrFeature $dvrFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $houses = $this->getHousesWithCameras($user, $request->houseId, $houseFeature, $cameraFeature);

        return user_response(200, $this->convertCameras($houses, $dvrFeature, $user));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/common', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function common(DvrFeature $dvrFeature): ResponseInterface
    {
        $cameras = DeviceCamera::fetchAll(criteria()->equal('common', 1));

        return user_response(data: array_map(fn(DeviceCamera $camera) => $dvrFeature->convertCameraForSubscriber($camera->toArrayMap([
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
        ]), null), $cameras))
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

        $identifier = $dvr->identifier($camera, $request->time ?? time());

        if (!$identifier)
            return user_response(404, message: 'Идентификатор не найден');

        try {
            $cache->set('dvr:' . $identifier->value, [$identifier->start, $identifier->end, $request->id, null]);

            return user_response(data: $identifier);
        } catch (Throwable $throwable) {
            file_logger('dvr')->error($throwable);
        }

        return user_response(500, message: 'Ошибка состояния камеры');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get('/{cameraId}')]
    public function show(CameraShowRequest $request, int $cameraId, CameraFeature $cameraFeature, DvrFeature $dvrFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $find = false;

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] == $request->houseId) {
                $find = true;

                break;
            }
        }

        if (!$find)
            return user_response(404, message: 'Камера не найдена');

        $camera = $cameraFeature->getCamera($cameraId);

        if (!$camera)
            return user_response(404, message: 'Камера не найдена');

        return user_response(data: $dvrFeature->convertCameraForSubscriber($camera, $user));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/events')]
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

    private function getHousesWithCameras(array $user, ?int $filterHouseId, HouseFeature $houseFeature, CameraFeature $cameraFeature): array
    {
        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($filterHouseId != null && $flat['addressHouseId'] != $filterHouseId)
                continue;

            $flatDetail = $houseFeature->getFlat($flat['flatId']);

            if ($flatDetail['autoBlock'] || $flatDetail['adminBlock'] || $flatDetail['manualBlock'])
                continue;

            $houseId = $flat['addressHouseId'];

            if (array_key_exists($houseId, $houses)) {
                $house = &$houses[$houseId];
            } else {
                $houses[$houseId] = [];
                $house = &$houses[$houseId];
                $house['houseId'] = strval($houseId);

                $house['cameras'] = $houseFeature->getCameras("houseId", $houseId);
                $house['doors'] = [];
            }

            $house['cameras'] = array_merge($house['cameras'], $houseFeature->getCameras("flatId", $flat['flatId']));

            foreach ($flatDetail['entrances'] as $entrance) {
                if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                    continue;
                }

                $e = $houseFeature->getEntrance($entrance['entranceId']);
                $door = [];

                if ($e['cameraId']) {
                    $cam = $cameraFeature->getCamera($e["cameraId"]);
                    $cam['houseId'] = $houseId;

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

        foreach ($houses as $house_key => $h) {
            $houses[$house_key]['doors'] = array_values($h['doors']);

            unset($houses[$house_key]['cameras']);

            foreach ($h['cameras'] as $camera) {
                if ($camera['cameraId'] === null)
                    continue;

                if (array_key_exists($camera['cameraId'], $ids))
                    continue;

                $ids[$camera['cameraId']] = true;

                $result[] = $dvrFeature->convertCameraForSubscriber($camera, $user);
            }
        }

        usort($result, static fn(array $a, array $b) => strcmp($a['name'], $b['name']));

        return $result;
    }
}