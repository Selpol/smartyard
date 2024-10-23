<?php

namespace Selpol\Controller\Internal;

use Selpol\Device\Ip\Camera\CameraDevice;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Request\Internal\FrsCallbackRequest;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Route\RouteController;
use Selpol\Service\PrometheusService;
use Selpol\Service\RedisService;
use Selpol\Validator\Exception\ValidatorException;

#[Controller('/internal/frs')]
readonly class FrsController extends RouteController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Post('/callback')]
    public function callback(FrsCallbackRequest $request, FrsFeature $frsFeature, BlockFeature $blockFeature, PlogFeature $plogFeature, RedisService $redisService, PrometheusService $prometheusService): Response
    {
        $counter = $prometheusService->getCounter('frs', 'open', 'Frs open request', ['id', 'status']);

        $frs_key = "frs_key_" . $request->stream_id;

        if ($redisService->get($frs_key) != null) {
            $counter->incBy(1, [0, 0]);

            return response(204);
        }

        $entrance = $frsFeature->getEntranceByCameraId($request->stream_id);

        if ($entrance === false || $entrance === []) {
            $counter->incBy(1, [0, 0]);

            return response(204);
        }

        $flats = $frsFeature->getFlatsDetailByFaceId($request->faceId, $entrance["entranceId"]);

        if (count($flats) == 0) {
            $counter->incBy(1, [0, 0]);

            return response(204);
        }

        $find = false;

        foreach ($flats as $flat) {
            foreach ($flat['entrances'] as $flatEntrance) {
                if ($flatEntrance['entranceId'] === $entrance['entranceId']) {
                    $find = true;

                    if ($blockFeature->getFirstBlockForFlat($flat['flatId'], [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_FRS]) != null) {
                        $counter->incBy(1, [0, 0]);

                        return response(204);
                    }

                    break;
                }
            }
        }

        if (!$find) {
            $counter->incBy(1, [0, 0]);

            return response(204);
        }

        $domophone_id = $entrance["domophoneId"];
        $domophone_output = $entrance["domophoneOutput"];

        try {
            $model = intercom($domophone_id);
            $model->open($domophone_output);

            $redisService->setEx($frs_key, config_get('feature.frs.open_door_timeout'), 1);

            $counter->incBy(1, [$domophone_id, 1]);

            $plogFeature->addDoorOpenDataById(time(), $domophone_id, PlogFeature::EVENT_OPENED_BY_FACE, $domophone_output, $request->faceId . "|" . $request->eventId);
        } catch (Exception) {
            return user_response(404);
        }

        return response(204);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get('/camshot/{id}')]
    public function camshot(PrometheusService $service, int $id): Response
    {
        $counter = $service->getCounter('frs', 'screenshot', 'Frs screenshot request', ['id', 'status']);

        $camera = camera($id);

        if (!$camera instanceof CameraDevice) {
            $counter->incBy(1, [$id, 0]);

            return response(204);
        }

        $counter->incBy(1, [$id, 1]);

        return response(headers: ['Content-Type' => ['image/jpeg']])->withBody($camera->getScreenshot());
    }

    #[Get('/face/{uuid}')]
    public function face(string $uuid, FileFeature $feature): Response
    {
        return response()
            ->withHeader('Content-Type', 'image/jpeg')
            ->withBody(stream($feature->getFileStream($feature->fromGUIDv4($uuid))));
    }
}