<?php

namespace Selpol\Controller\Mobile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\RbtController;
use Selpol\Controller\Request\Mobile\PlogDaysRequest;
use Selpol\Controller\Request\Mobile\PlogIndexRequest;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Throwable;

#[Controller('/mobile/address', includes: [BlockMiddleware::class => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_EVENT]])]
readonly class PlogController extends RbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('/plog')]
    public function index(PlogIndexRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature, FrsFeature $frsFeature, BlockFeature $blockFeature): Response
    {
        if ($block = $blockFeature->getFirstBlockForFlat($request->flatId, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_EVENT]))
            return user_response(403, message: 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : ''));

        $user = $this->getUser()->getOriginalValue();

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

        $f = in_array($request->flatId, $flat_ids);
        if (!$f)
            return user_response(404, message: 'У абонента нет доступа');

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $request->flatId) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        $flat_details = $houseFeature->getFlat($request->flatId);
        $plog_access = $flat_details['plog'];

        if ($plog_access == PlogFeature::ACCESS_DENIED || $plog_access == PlogFeature::ACCESS_RESTRICTED_BY_ADMIN || $plog_access == PlogFeature::ACCESS_OWNER_ONLY && !$flat_owner)
            return user_response(403, message: 'Недостаточно прав на просмотр событий');

        try {
            $date = date('Ymd', strtotime($request->day));

            if ($result = $plogFeature->getDetailEventsByDay($request->flatId, $date)) {
                $events_details = [];

                foreach ($result as $row) {
                    $e_details = [];
                    $e_details['date'] = date('Y-m-d H:i:s', $row[PlogFeature::COLUMN_DATE]);
                    $e_details['uuid'] = $row[PlogFeature::COLUMN_EVENT_UUID];
                    $e_details['image'] = $row[PlogFeature::COLUMN_IMAGE_UUID];
                    $e_details['previewType'] = $row[PlogFeature::COLUMN_PREVIEW];

                    $domophone = json_decode($row[PlogFeature::COLUMN_DOMOPHONE]);

                    if (isset($domophone->domophone_id) && isset($domophone->domophone_output)) {
                        $e_details['objectId'] = strval($domophone->domophone_id);
                        $e_details['objectType'] = "0";
                        $e_details['objectMechanizma'] = strval($domophone->domophone_output);
                        if (isset($domophone->domophone_description)) {
                            $e_details['mechanizmaDescription'] = $domophone->domophone_description;
                        } else {
                            $e_details['mechanizmaDescription'] = '';
                        }
                    }

                    $event_type = (int)$row[PlogFeature::COLUMN_EVENT];
                    $e_details['event'] = strval($event_type);
                    $face = json_decode($row[PlogFeature::COLUMN_FACE], false);

                    if (isset($face->width) && $face->width > 0 && isset($face->height) && $face->height > 0) {
                        $e_details['detailX']['face'] = ['left' => $face->left, 'top' => $face->top, 'width' => $face->width, 'height' => $face->height];

                        $e_details['detailX']['flags'] = [FrsFeature::FLAG_CAN_LIKE];
                        $face_id = null;

                        if (isset($face->faceId) && $face->faceId > 0)
                            $face_id = $face->faceId;

                        $subscriber_id = (int)$user['subscriberId'];

                        if ($frsFeature->isLikedFlag($request->flatId, $subscriber_id, $face_id, $row[PlogFeature::COLUMN_EVENT_UUID], $flat_owner)) {
                            $e_details['detailX']['flags'][] = FrsFeature::FLAG_LIKED;
                            $e_details['detailX']['flags'][] = FrsFeature::FLAG_CAN_DISLIKE;
                        }
                    }

                    if (isset($face->faceId) && $face->faceId > 0)
                        $e_details['detailX']['faceId'] = strval($face->faceId);

                    $phones = json_decode($row[PlogFeature::COLUMN_PHONES]);

                    switch ($event_type) {
                        case PlogFeature::EVENT_UNANSWERED_CALL:
                        case PlogFeature::EVENT_ANSWERED_CALL:
                            $e_details['detailX']['opened'] = ($row[PlogFeature::COLUMN_OPENED] == 1) ? 't' : 'f';

                            break;

                        case PlogFeature::EVENT_OPENED_BY_KEY:
                            $e_details['detailX']['key'] = strval($row[PlogFeature::COLUMN_RFID]);

                            break;

                        case PlogFeature::EVENT_OPENED_BY_APP:
                            if ($phones->user_phone)
                                $e_details['detailX']['phone'] = strval($phones->user_phone);

                            break;

                        case PlogFeature::EVENT_OPENED_BY_FACE:
                            break;

                        case PlogFeature::EVENT_OPENED_BY_CODE:
                            $e_details['detailX']['code'] = strval($row[PlogFeature::COLUMN_CODE]);

                            break;

                        case PlogFeature::EVENT_OPENED_GATES_BY_CALL:
                            if ($phones->user_phone)
                                $e_details['detailX']['phoneFrom'] = strval($phones->user_phone);

                            if ($phones->gate_phone)
                                $e_details['detailX']['phoneTo'] = strval($phones->gate_phone);

                            break;
                    }

                    if ((int)$row[PlogFeature::COLUMN_PREVIEW]) {
                        $img_uuid = $row[PlogFeature::COLUMN_IMAGE_UUID];
                        $url = config_get('api.mobile') . "/address/plogCamshot/$img_uuid";
                        $e_details['preview'] = $url;
                    }

                    $events_details[] = $e_details;
                }

                return user_response(data: $events_details);
            } else {
                return user_response(data: []);
            }
        } catch (Throwable $throwable) {
            file_logger('plog')->debug($throwable);

            return user_response(500);
        }
    }

    #[Get('/plogCamshot/{uuid}')]
    public function camshot(string $uuid, FileFeature $fileFeature): Response
    {
        return response()
            ->withHeader('Content-Type', 'image/jpeg')
            ->withBody(stream($fileFeature->getFileStream($fileFeature->fromGUIDv4($uuid))));
    }

    #[Post('/plogDays')]
    public function days(PlogDaysRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($request->flatId, $flat_ids);

        if (!$f)
            return user_response(404, message: 'Квартира не найдена');

        $flat_owner = false;
        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $request->flatId) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        $flat_details = $houseFeature->getFlat($request->flatId);
        $plog_access = $flat_details['plog'];

        if ($plog_access == PlogFeature::ACCESS_DENIED || $plog_access == PlogFeature::ACCESS_RESTRICTED_BY_ADMIN || $plog_access == PlogFeature::ACCESS_OWNER_ONLY && !$flat_owner)
            return user_response(403, message: 'Недостаточно прав на просмотр событий');

        $filter_events = false;

        if ($request->events) {
            $filter_events = explode(',', $request->events);
            $t = [];

            foreach ($filter_events as $e)
                $t[(int)$e] = 1;

            $filter_events = [];

            foreach ($t as $e => $one)
                $filter_events[] = $e;

            sort($filter_events);

            $filter_events = implode(',', $filter_events);
        }

        try {
            $result = $plogFeature->getEventsDays($request->flatId, $filter_events);

            if ($result)
                return user_response(data: $result);

            return user_response(data: []);
        } catch (Throwable $throwable) {
            file_logger('plog')->debug($throwable);

            return user_response(500);
        }
    }
}