<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Http\Response;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;

class CameraController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function all(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator($this->request->getParsedBody(), ['houseId' => [Rule::id()]]);

        $house_id = $validate['houseId'];
        $households = container(HouseFeature::class);

        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] != $house_id)
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

            $flatDetail = $households->getFlat($flat['flatId']);

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

                $dvr = container(DvrFeature::class)->getDVRServerByStream($camera['dvrStream']);

                $result[] = [
                    "id" => $camera['cameraId'],
                    "name" => $camera['name'],
                    "lat" => strval($camera['lat']),
                    "url" => $camera['dvrStream'],
                    "token" => container(DvrFeature::class)->getDVRTokenForCam($camera, $user['subscriberId']),
                    "lon" => strval($camera['lon']),
                    "serverType" => $dvr['type'],
                    'timezone' => $camera['timezone']
                ];
            }
        }

        if (count($result))
            return $this->rbtResponse(200, $result);

        return $this->rbtResponse(404, message: 'Камеры не найдены');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function events(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $body = $this->request->getParsedBody();

        $validate = validator($body, [
            'cameraId' => [Rule::id()],
            'date' => [Filter::default(1), Rule::int(), Rule::min(0), Rule::max(14), Rule::nonNullable()]
        ]);

        $households = container(HouseFeature::class);

        $domophoneId = $households->getDomophoneIdByEntranceCameraId($validate['cameraId']);

        if (is_null($domophoneId))
            return $this->rbtResponse(404, message: 'Домофон не найден');

        $flats = array_filter(
            array_map(static fn(array $item) => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $user['flats']),
            static function (array $flat) use ($households) {
                $plog = $households->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == PlogFeature::ACCESS_ALL || $plog == PlogFeature::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0)
            return $this->rbtResponse(404, message: 'Квартира у абонента не найдена');

        $events = container(PlogFeature::class)->getEventsByFlatsAndDomophone($flatsId, $domophoneId, $validate['date']);

        if ($events)
            return $this->rbtResponse(200, array_map(static fn(array $item) => $item['date'], $events));

        return $this->rbtResponse(404, message: 'События не найдены');
    }
}