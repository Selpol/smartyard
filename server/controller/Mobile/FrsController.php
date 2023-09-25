<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Http\Response;
use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorException;

class FrsController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function index(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flatId = $this->getRoute()->getParamIntOrThrow('flatId');

        $flatIds = array_map(static fn($item) => $item['flatId'], $user['flats']);

        $f = in_array($flatId, $flatIds);

        if (!$f)
            $this->rbtResponse(404, message: 'Квартира не найдена');

        $flatOwner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flatId) {
                $flatOwner = ($flat['role'] == 0);

                break;
            }

        $faces = container(FrsFeature::class)->listFaces($flatId, $this->getUser()->getOriginalValue(), $flatOwner);
        $result = [];

        foreach ($faces as $face)
            $result[] = ['faceId' => $face[FrsFeature::P_FACE_ID], 'image' => config('api.mobile') . '/address/plogCamshot/' . $face[FrsFeature::P_FACE_IMAGE]];

        return $this->rbtResponse(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function store(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator(['eventId' => $this->getRoute()->getParam('eventId')], ['eventId' => [Rule::required(), Rule::uuid(), Rule::nonNullable()]]);

        $frs = container(FrsFeature::class);

        $eventData = container(PlogFeature::class)->getEventDetails($validate['eventId']);

        if ($eventData === false)
            return $this->rbtResponse(404, message: 'Событие не найдено');

        if ($eventData[PlogFeature::COLUMN_PREVIEW] == PlogFeature::PREVIEW_NONE)
            $this->rbtResponse(404, message: 'Нет кадра события');

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

        $flat_id = (int)$eventData[PlogFeature::COLUMN_FLAT_ID];
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            $this->rbtResponse(404, message: 'Квартира не найдена');

        $households = container(HouseFeature::class);
        $domophone = json_decode($eventData[PlogFeature::COLUMN_DOMOPHONE], false);
        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $domophone->domophone_id, 'output' => $domophone->domophone_output]);

        if ($entrances && $entrances[0]) {
            $cameras = $households->getCameras('id', $entrances[0]['cameraId']);

            if ($cameras && $cameras[0]) {
                $face = json_decode($eventData[PlogFeature::COLUMN_FACE], true);
                $result = $frs->registerFace($cameras[0], $validate['eventId'], $face['left'] ?? 0, $face['top'] ?? 0, $face['width'] ?? 0, $face['height'] ?? 0);

                if (!isset($result[FrsFeature::P_FACE_ID]))
                    return $this->rbtResponse(400, message: $result[FrsFeature::P_MESSAGE]);

                $face_id = (int)$result[FrsFeature::P_FACE_ID];
                $subscriber_id = (int)$user['subscriberId'];

                $frs->attachFaceId($face_id, $flat_id, $subscriber_id);

                return $this->rbtResponse();
            }
        }

        return $this->rbtResponse();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function delete(): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $validate = validator(['eventId' => $this->request->getQueryParam('eventId')], ['eventId' => [Rule::required(), Rule::uuid(), Rule::nonNullable()]]);

        $frs = container(FrsFeature::class);

        $face_id = null;
        $face_id2 = null;

        if ($validate['eventId']) {
            $eventData = container(PlogFeature::class)->getEventDetails($validate['eventId']);
            if (!$eventData)
                $this->rbtResponse(404, message: 'Событие не найдено');

            $flat_id = (int)$eventData[PlogFeature::COLUMN_FLAT_ID];

            $face = json_decode($eventData[PlogFeature::COLUMN_FACE]);
            if (isset($face->faceId) && $face->faceId > 0)
                $face_id = (int)$face->faceId;

            $face_id2 = $frs->getRegisteredFaceId($validate['eventId']);

            if ($face_id2 === false)
                $face_id2 = null;
        } else {
            $flat_id = (int)$this->request->getQueryParam('flatId');
            $face_id = (int)$this->request->getQueryParam('faceId');
        }

        if (($face_id === null || $face_id <= 0) && ($face_id2 === null || $face_id2 <= 0))
            return $this->rbtResponse(404);

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return $this->rbtResponse(404);

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flat_id) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        if ($flat_owner) {
            if ($face_id > 0) $frs->detachFaceIdFromFlat($face_id, $flat_id);
            if ($face_id2 > 0) $frs->detachFaceIdFromFlat($face_id2, $flat_id);
        } else {
            $subscriber_id = (int)$user['subscriberId'];

            if ($face_id > 0) $frs->detachFaceId($face_id, $subscriber_id);
            if ($face_id2 > 0) $frs->detachFaceId($face_id2, $subscriber_id);
        }

        return $this->rbtResponse();
    }
}