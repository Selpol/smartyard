<?php

namespace Selpol\Controller\Internal;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Internal\FrsCallbackRequest;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Service\RedisService;
use Selpol\Validator\Exception\ValidatorException;

#[Controller('/internal/frs')]
readonly class FrsController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Post('/callback')]
    public function callback(FrsCallbackRequest $request, FrsFeature $frsFeature, RedisService $redisService): Response
    {
        $frs_key = "frs_key_" . $request->stream_id;

        if ($redisService->get($frs_key) != null)
            return response(204);

        $entrance = $frsFeature->getEntranceByCameraId($request->stream_id);

        if (!$entrance)
            return response(204);

        $flats = $frsFeature->getFlatsDetailByFaceId($request->faceId, $entrance["entranceId"]);

        if (!$flats)
            return response(204);

        $find = false;

        foreach ($flats as $flat) {
            foreach ($flat['entrances'] as $flatEntrance) {
                if ($flatEntrance['entranceId'] === $entrance['entranceId']) {
                    $find = true;

                    if ($flat['autoBlock'] || $flat['adminBlock'] || $flat['manualBlock'])
                        return response(204);

                    break;
                }
            }
        }

        if (!$find)
            return response(204);

        $domophone_id = $entrance["domophoneId"];
        $domophone_output = $entrance["domophoneOutput"];

        try {
            $model = intercom($domophone_id);
            $model->open($domophone_output);

            $redisService->setEx($frs_key, config_get('feature.frs.open_door_timeout'), 1);

            container(PlogFeature::class)->addDoorOpenDataById(time(), $domophone_id, PlogFeature::EVENT_OPENED_BY_FACE, $domophone_output, $request->faceId . "|" . $request->eventId);
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
    public function camshot(int $id): Response
    {
        $camera = camera($id);

        if (!$camera)
            return response(204);

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