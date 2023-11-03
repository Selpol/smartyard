<?php

namespace Selpol\Controller\Internal;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use RedisException;
use Selpol\Controller\RbtController;
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
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    #[Post('/callback')]
    public function callback(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $camera_id = $request->getQueryParams()['stream_id'];

        $face_id = (int)$body[FrsFeature::P_FACE_ID];
        $event_id = (int)$body[FrsFeature::P_EVENT_ID];

        if (!isset($camera_id) || $face_id == 0 || $event_id == 0)
            return response(204);

        $frs_key = "frs_key_" . $camera_id;

        $redis = container(RedisService::class)->getConnection();

        if ($redis->get($frs_key) != null)
            return response(204);

        $entrance = container(FrsFeature::class)->getEntranceByCameraId($camera_id);

        if (!$entrance)
            return response(204);

        $flats = container(FrsFeature::class)->getFlatsByFaceId($face_id, $entrance["entranceId"]);

        if (!$flats)
            return response(204);

        $domophone_id = $entrance["domophoneId"];
        $domophone_output = $entrance["domophoneOutput"];

        try {
            $model = intercom($domophone_id);
            $model->open($domophone_output);

            $redis->set($frs_key, 1, config_get('feature.frs.open_door_timeout'));

            container(PlogFeature::class)->addDoorOpenDataById(time(), $domophone_id, PlogFeature::EVENT_OPENED_BY_FACE, $domophone_output, $face_id . "|" . $event_id);
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
}