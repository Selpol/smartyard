<?php

namespace Selpol\Controller\Internal;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Controller\Controller;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Http\Response;
use Selpol\Service\RedisService;
use Selpol\Validator\ValidatorException;

class FrsController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    public function callback(): Response
    {
        $body = $this->request->getParsedBody();

        $camera_id = $this->request->getQueryParam('stream_id');

        $face_id = (int)$body[FrsFeature::P_FACE_ID];
        $event_id = (int)$body[FrsFeature::P_EVENT_ID];

        if (!isset($camera_id) || $face_id == 0 || $event_id == 0)
            return $this->rbtResponse(204);

        $frs_key = "frs_key_" . $camera_id;

        $redis = container(RedisService::class)->getRedis();

        if ($redis->get($frs_key) != null)
            return $this->rbtResponse(204);

        $entrance = container(FrsFeature::class)->getEntranceByCameraId($camera_id);

        if (!$entrance)
            return $this->rbtResponse(204);

        $flats = container(FrsFeature::class)->getFlatsByFaceId($face_id, $entrance["entranceId"]);

        if (!$flats)
            return $this->rbtResponse(204);

        $domophone_id = $entrance["domophoneId"];
        $domophone_output = $entrance["domophoneOutput"];

        try {
            $model = intercom($domophone_id);
            $model->open($domophone_output);

            $redis->set($frs_key, 1, config('feature.frs.open_door_timeout'));

            container(PlogFeature::class)->addDoorOpenDataById(time(), $domophone_id, PlogFeature::EVENT_OPENED_BY_FACE, $domophone_output, $face_id . "|" . $event_id);
        } catch (Exception) {
            return $this->rbtResponse(404);
        }

        return $this->rbtResponse(204);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function camshot(): Response
    {
        $model = camera($this->getRoute()->getParamIdOrThrow('id'));

        if (!$model)
            return $this->rbtResponse(204);

        return $this->response()->withBody($model->getScreenshot())->withHeader('Content-Type', 'image/jpeg');
    }
}