<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\CameraEventsRequest;
use Selpol\Controller\Request\Mobile\CameraIndexRequest;
use Selpol\Controller\Request\Mobile\CameraShowRequest;
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

#[Controller('/mobile/cctv')]
readonly class CameraController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/all')]
    public function index(CameraIndexRequest $request, HouseFeature $houseFeature): ResponseInterface
    {
        $user = $this->getUser()->getOriginalValue();

        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] != $request->houseId)
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
                    $cam = container(CameraFeature::class)->getCamera($e["cameraId"]);
                    $house['cameras'][] = $cam;
                }

                $house['doors'][$entrance['entranceId']] = $door;
            }
        }

        $result = [];

        foreach ($houses as $house_key => $h) {
            $houses[$house_key]['doors'] = array_values($h['doors']);

            unset($houses[$house_key]['cameras']);

            foreach ($h['cameras'] as $camera) {
                if ($camera['cameraId'] === null)
                    continue;

                $result[] = $this->convertCamera($camera, $user);
            }
        }

        return user_response(200, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Get('/common', excludes: [AuthMiddleware::class, SubscriberMiddleware::class])]
    public function common(): ResponseInterface
    {
        $cameras = DeviceCamera::fetchAll(criteria()->equal('common', 1));

        return user_response(data: array_map(fn(DeviceCamera $camera) => $this->convertCamera($camera->toArrayMap([
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
        ]), null), $cameras));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get('/{cameraId}')]
    public function show(CameraShowRequest $request, int $cameraId, CameraFeature $cameraFeature): ResponseInterface
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

        return user_response(data: $this->convertCamera($camera, $user));
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

    /**
     * @throws NotFoundExceptionInterface
     */
    private function convertCamera(array $camera, ?array $user): array
    {
        $dvr = container(DvrFeature::class)->getDVRServerByCamera($camera);

        return [
            "id" => $camera['cameraId'],
            "name" => $camera['name'],
            "lat" => strval($camera['lat']),
            "lon" => strval($camera['lon']),
            'timezone' => $camera['timezone'],
            "url" => container(DvrFeature::class)->getUrlForCamera($dvr, $camera),
            "token" => container(DvrFeature::class)->getTokenForCamera($dvr, $camera, $user ? $user['subscriberId'] : null),
            "serverType" => $dvr?->type ?? 'flussonic',
            'domophoneId' => container(HouseFeature::class)->getDomophoneIdByEntranceCameraId($camera['cameraId'])
        ];
    }
}