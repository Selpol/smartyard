<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Validator\Exception\ValidatorException;

#[Controller('/mobile/cctv')]
readonly class CameraController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/all')]
    public function index(ServerRequestInterface $request): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($request->getParsedBody(), ['houseId' => rule()->id()]);

        $house_id = $validate['houseId'];
        $households = container(HouseFeature::class);

        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] != $house_id)
                continue;

            $flatDetail = $households->getFlat($flat['flatId']);

            if ($flatDetail['autoBlock'] || $flatDetail['adminBlock'] || $flatDetail['manualBlock'])
                continue;

            $houseId = $flat['addressHouseId'];

            if (array_key_exists($houseId, $houses)) {
                $house = &$houses[$houseId];

            } else {
                $houses[$houseId] = [];
                $house = &$houses[$houseId];
                $house['houseId'] = strval($houseId);

                $house['cameras'] = $households->getCameras("houseId", $houseId);
                $house['doors'] = [];
            }

            $house['cameras'] = array_merge($house['cameras'], $households->getCameras("flatId", $flat['flatId']));

            foreach ($flatDetail['entrances'] as $entrance) {
                if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                    continue;
                }

                $e = $households->getEntrance($entrance['entranceId']);
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

        if (count($result))
            return user_response(200, $result);

        return user_response(404, message: 'Камеры не найдены');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get('/{cameraId}')]
    public function show(ServerRequestInterface $request, int $cameraId): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $houseId = rule()->id()->onItem('houseId', $request->getQueryParams());

        $find = false;

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] == $houseId) {
                $find = true;

                break;
            }
        }

        if (!$find)
            return user_response(404, message: 'Камера не найдена');

        $camera = container(CameraFeature::class)->getCamera($cameraId);

        if (!$camera)
            return user_response(404, message: 'Камера не найдена');

        return user_response(data: $this->convertCamera($camera, $user));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/events')]
    public function events(ServerRequestInterface $request): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $body = $request->getParsedBody();

        $validate = validator($body, [
            'cameraId' => rule()->id(),
            'date' => [filter()->default(1), rule()->int()->clamp(0, 14)->nonNullable()]
        ]);

        $households = container(HouseFeature::class);

        $domophoneId = $households->getDomophoneIdByEntranceCameraId($validate['cameraId']);

        if (is_null($domophoneId))
            return user_response(404, message: 'Домофон не найден');

        $flats = array_filter(
            array_map(static fn(array $item) => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $user['flats']),
            static function (array $flat) use ($households) {
                $plog = $households->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == PlogFeature::ACCESS_ALL || $plog == PlogFeature::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0)
            return user_response(404, message: 'Квартира у абонента не найдена');

        $events = container(PlogFeature::class)->getEventsByFlatsAndDomophone($flatsId, $domophoneId, $validate['date']);

        if ($events)
            return user_response(200, array_map(static fn(array $item) => $item['date'], $events));

        return user_response(404, message: 'События не найдены');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function convertCamera(array $camera, array $user): array
    {
        $dvr = container(DvrFeature::class)->getDVRServerByStream($camera['dvrStream']);

        return [
            "id" => $camera['cameraId'],
            "name" => $camera['name'],
            "lat" => strval($camera['lat']),
            "url" => $camera['dvrStream'],
            "token" => container(DvrFeature::class)->getDVRTokenForCam($camera, $user['subscriberId']),
            "lon" => strval($camera['lon']),
            "serverType" => $dvr?->type ?? 'flussonic',
            'domophoneId' => container(HouseFeature::class)->getDomophoneIdByEntranceCameraId($camera['cameraId']),
            'timezone' => $camera['timezone']
        ];
    }
}