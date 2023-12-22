<?php

namespace Selpol\Controller\Internal;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Internal\FrsCallbackRequest;
use Selpol\Feature\Block\BlockFeature;
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
    public function callback(FrsCallbackRequest $request, FrsFeature $frsFeature, BlockFeature $blockFeature, PlogFeature $plogFeature, RedisService $redisService): Response
    {
        $frs_key = "frs_key_" . $request->stream_id;

        if ($redisService->get($frs_key) != null)
            return response(204);

        $entrance = $frsFeature->getEntranceByCameraId($request->stream_id);

        if (!$entrance)
            return response(204);

        $flats = $frsFeature->getFlatsByFaceId($request->faceId, $entrance["entranceId"]);
        $flats = array_filter($flats, static fn(int $id) => $blockFeature->getFirstBlockForFlat($id, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_FRS]) == null);

        if (count($flats) == 0)
            return response(204);

        $domophone_id = $entrance["domophoneId"];
        $domophone_output = $entrance["domophoneOutput"];

        try {
            $model = intercom($domophone_id);
            $model->open($domophone_output);

            $redisService->setEx($frs_key, config_get('feature.frs.open_door_timeout'), 1);

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