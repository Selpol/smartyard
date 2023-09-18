<?php

namespace Selpol\Controller\mobile;

use backends\frs\frs;
use backends\plog\plog;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Validator\Rule;
use Throwable;

class PlogController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Response
    {
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), ['flatId' => [Rule::id()], 'day' => [Rule::required(), Rule::nonNullable()]]);

        $households = backend("households");
        $flat_id = $validate['flatId'];

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

        $f = in_array($flat_id, $flat_ids);
        if (!$f)
            return $this->rbtResponse(404, message: 'У абонента нет доступа');

        $plog = backend("plog");

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flat_id) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        $flat_details = $households->getFlat($flat_id);
        $plog_access = $flat_details['plog'];

        if ($plog_access == $plog::ACCESS_DENIED || $plog_access == $plog::ACCESS_RESTRICTED_BY_ADMIN || $plog_access == $plog::ACCESS_OWNER_ONLY && !$flat_owner)
            return $this->rbtResponse(403, message: 'Недостаточно прав');

        try {
            $date = date('Ymd', strtotime($validate['day']));
            $result = $plog->getDetailEventsByDay($flat_id, $date);

            if ($result) {
                $events_details = [];

                foreach ($result as $row) {
                    $e_details = [];
                    $e_details['date'] = date('Y-m-d H:i:s', $row[plog::COLUMN_DATE]);
                    $e_details['uuid'] = $row[plog::COLUMN_EVENT_UUID];
                    $e_details['image'] = $row[plog::COLUMN_IMAGE_UUID];
                    $e_details['previewType'] = $row[plog::COLUMN_PREVIEW];

                    $domophone = json_decode($row[plog::COLUMN_DOMOPHONE]);
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

                    $event_type = (int)$row[plog::COLUMN_EVENT];
                    $e_details['event'] = strval($event_type);
                    $face = json_decode($row[plog::COLUMN_FACE], false);
                    if (isset($face->width) && $face->width > 0 && isset($face->height) && $face->height > 0) {
                        $e_details['detailX']['face'] = [
                            'left' => $face->left,
                            'top' => $face->top,
                            'width' => $face->width,
                            'height' => $face->height
                        ];
                        $frs = backend("frs");
                        if ($frs) {
                            $e_details['detailX']['flags'] = [frs::FLAG_CAN_LIKE];
                            $face_id = null;
                            if (isset($face->faceId) && $face->faceId > 0) {
                                $face_id = $face->faceId;
                            }
                            $subscriber_id = (int)$user['subscriberId'];
                            if ($frs->isLikedFlag($flat_id, $subscriber_id, $face_id, $row[plog::COLUMN_EVENT_UUID], $flat_owner)) {
                                $e_details['detailX']['flags'][] = frs::FLAG_LIKED;
                                $e_details['detailX']['flags'][] = frs::FLAG_CAN_DISLIKE;
                            }
                        }
                    }
                    if (isset($face->faceId) && $face->faceId > 0) {
                        $e_details['detailX']['faceId'] = strval($face->faceId);
                    }

                    $phones = json_decode($row[plog::COLUMN_PHONES]);

                    switch ($event_type) {
                        case plog::EVENT_UNANSWERED_CALL:
                        case plog::EVENT_ANSWERED_CALL:
                            $e_details['detailX']['opened'] = $row[plog::COLUMN_OPENED] == 1;
                            break;

                        case plog::EVENT_OPENED_BY_KEY:
                            $e_details['detailX']['key'] = strval($row[plog::COLUMN_RFID]);
                            break;

                        case plog::EVENT_OPENED_BY_APP:
                            if ($phones->user_phone) {
                                $e_details['detailX']['phone'] = strval($phones->user_phone);
                            }
                            break;

                        case plog::EVENT_OPENED_BY_FACE:
                            break;

                        case plog::EVENT_OPENED_BY_CODE:
                            $e_details['detailX']['code'] = strval($row[plog::COLUMN_CODE]);
                            break;

                        case plog::EVENT_OPENED_GATES_BY_CALL:
                            if ($phones->user_phone) {
                                $e_details['detailX']['phoneFrom'] = strval($phones->user_phone);
                            }
                            if ($phones->gate_phone) {
                                $e_details['detailX']['phoneTo'] = strval($phones->gate_phone);
                            }
                            break;
                    }
                    if ((int)$row[plog::COLUMN_PREVIEW]) {
                        $img_uuid = $row[plog::COLUMN_IMAGE_UUID];
                        $url = config('api.mobile') . "/address/plogCamshot/$img_uuid";
                        $e_details['preview'] = $url;
                    }

                    $events_details[] = $e_details;
                }
                return $this->rbtResponse(data: $events_details);
            } else {
                return $this->rbtResponse(404, message: 'События не найдены');
            }
        } catch (Throwable $throwable) {
            logger('plog')->debug($throwable);

            return $this->rbtResponse(500);
        }
    }

    public function camshot(): Response
    {
        $files = backend('files');
        $uuid = $files->fromGUIDv4($this->getRoute()->getParam('uuid'));

        return $this->response()->withStream($files->getFileStream($uuid))->withHeader('Content-Type', 'image/jpeg');
    }

    public function days(): Response
    {
        $user = $this->getSubscriber();

        $validate = validator($this->request->getParsedBody(), ['flatId' => [Rule::id()], 'events' => [Rule::length(max: 64)]]);

        $households = backend("households");
        $flat_id = $validate['flatId'];

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return $this->rbtResponse(404, message: 'Квартира не найдена');

        $plog = backend("plog");

        $flat_owner = false;
        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flat_id) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        $flat_details = $households->getFlat($flat_id);
        $plog_access = $flat_details['plog'];

        if ($plog_access == $plog::ACCESS_DENIED || $plog_access == $plog::ACCESS_RESTRICTED_BY_ADMIN || $plog_access == $plog::ACCESS_OWNER_ONLY && !$flat_owner)
            return $this->rbtResponse(403, message: 'Недостаточно прав');

        $filter_events = false;

        if ($validate['events']) {
            $filter_events = explode(',', $validate['events']);
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
            $result = $plog->getEventsDays($flat_id, $filter_events);

            if ($result)
                return $this->rbtResponse(200, $result);

            return $this->rbtResponse(404, message: 'События не найдены');
        } catch (Throwable $throwable) {
            logger('plog')->debug($throwable);

            return $this->rbtResponse(500);
        }
    }
}