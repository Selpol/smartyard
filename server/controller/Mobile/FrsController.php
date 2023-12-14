<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\FrsDeleteRequest;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Validator\Exception\ValidatorException;

#[Controller('/mobile/frs')]
readonly class FrsController extends RbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    #[Get('/{flatId}')]
    public function index(int $flatId, FrsFeature $frsFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flatIds = array_map(static fn($item) => $item['flatId'], $user['flats']);

        $f = in_array($flatId, $flatIds);

        if (!$f)
            user_response(404, message: 'Квартира не найдена');

        $flatOwner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flatId) {
                $flatOwner = ($flat['role'] == 0);

                break;
            }

        $faces = $frsFeature->listFaces($flatId, $this->getUser()->getIdentifier(), $flatOwner);
        $result = [];

        foreach ($faces as $face)
            $result[] = ['faceId' => $face[FrsFeature::P_FACE_ID], 'image' => config_get('api.mobile') . '/address/plogCamshot/' . $face[FrsFeature::P_FACE_IMAGE]];

        return user_response(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Post('/{eventId}')]
    public function store(string $eventId, FrsFeature $frsFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator(['eventId' => $eventId], ['eventId' => rule()->required()->uuid()->nonNullable()]);

        $eventData = container(PlogFeature::class)->getEventDetails($validate['eventId']);

        if ($eventData === false)
            return user_response(404, message: 'Событие не найдено');

        if ($eventData[PlogFeature::COLUMN_PREVIEW] == PlogFeature::PREVIEW_NONE)
            return user_response(404, message: 'Нет кадра события');

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

        $flat_id = (int)$eventData[PlogFeature::COLUMN_FLAT_ID];
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return user_response(404, message: 'Квартира не найдена');

        $households = container(HouseFeature::class);
        $domophone = json_decode($eventData[PlogFeature::COLUMN_DOMOPHONE], false);
        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $domophone->domophone_id, 'output' => $domophone->domophone_output]);

        if ($entrances && $entrances[0]) {
            $cameras = $households->getCameras('id', $entrances[0]['cameraId']);

            if ($cameras && $cameras[0]) {
                $face = json_decode($eventData[PlogFeature::COLUMN_FACE], true);
                $result = $frsFeature->registerFace($cameras[0], $validate['eventId'], $face['left'] ?? 0, $face['top'] ?? 0, $face['width'] ?? 0, $face['height'] ?? 0);

                if (!isset($result[FrsFeature::P_FACE_ID]))
                    return user_response(400, message: $result[FrsFeature::P_MESSAGE]);

                $face_id = (int)$result[FrsFeature::P_FACE_ID];
                $subscriber_id = (int)$user['subscriberId'];

                $frsFeature->attachFaceId($face_id, $flat_id, $subscriber_id);

                return user_response();
            }
        }

        return user_response();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Delete]
    public function delete(FrsDeleteRequest $request, FrsFeature $frsFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $face_id = null;
        $face_id2 = null;

        if ($request->eventId) {
            $eventData = container(PlogFeature::class)->getEventDetails($request->eventId);

            if (!$eventData)
                return user_response(404, message: 'Событие не найдено');

            $flat_id = (int)$eventData[PlogFeature::COLUMN_FLAT_ID];

            $face = json_decode($eventData[PlogFeature::COLUMN_FACE]);

            if (isset($face->faceId) && $face->faceId > 0)
                $face_id = (int)$face->faceId;

            $face_id2 = $frsFeature->getRegisteredFaceId($request->eventId);

            if ($face_id2 === false)
                $face_id2 = null;
        } else {
            $flat_id = $request->flat_id ?? $request->flatId;
            $face_id = $request->face_id ?? $request->faceId;
        }

        if (($face_id === null || $face_id <= 0) && ($face_id2 === null || $face_id2 <= 0))
            return user_response(404, message: 'Лицо не указано');

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return user_response(404, message: 'Квартира не найдена');

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flat_id) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        if ($flat_owner) {
            if ($face_id > 0) $frsFeature->detachFaceIdFromFlat($face_id, $flat_id);
            if ($face_id2 > 0) $frsFeature->detachFaceIdFromFlat($face_id2, $flat_id);
        } else {
            $subscriber_id = (int)$user['subscriberId'];

            if ($face_id > 0) $frsFeature->detachFaceId($face_id, $subscriber_id);
            if ($face_id2 > 0) $frsFeature->detachFaceId($face_id2, $subscriber_id);
        }

        return user_response();
    }
}