<?php

namespace Selpol\Controller\Mobile;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\MobileRbtController;
use Selpol\Controller\Request\Mobile\PlogDaysRequest;
use Selpol\Controller\Request\Mobile\PlogIndexRequest;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileStorage;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Middleware\Mobile\BlockFlatMiddleware;
use Selpol\Middleware\Mobile\BlockMiddleware;
use Selpol\Middleware\Mobile\FlatMiddleware;
use Selpol\Service\DatabaseService;
use Throwable;

#[Controller('/mobile/address', includes: [BlockMiddleware::class => ['code' => 200, 'body' => ['code' => 200, 'name' => 'OK', 'data' => []], 'services' => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_EVENT]]])]
readonly class PlogController extends MobileRbtController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post(
        '/plog',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId'],
            BlockFlatMiddleware::class => ['code' => 200, 'body' => ['code' => 200, 'name' => 'OK', 'data' => []], 'flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_EVENT]]
        ]
    )]
    public function index(PlogIndexRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature, FrsFeature $frsFeature, BlockFeature $blockFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        if ($blockFeature->getFirstBlockForFlat($request->flatId, [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_EVENT]) != null) {
            return user_response();
        }

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $request->flatId) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        $flat_details = $houseFeature->getFlat($request->flatId);
        $plog_access = $flat_details['plog'];

        if ($plog_access == PlogFeature::ACCESS_DENIED || $plog_access == PlogFeature::ACCESS_OWNER_ONLY && !$flat_owner) {
            return user_response(data: []);
        }

        try {
            $date = date('Ymd', strtotime($request->day));

            if ($result = $plogFeature->getDetailEventsByDay($request->flatId, $date)) {
                $events_details = [];

                foreach ($result as $row) {
                    $e_details = [];
                    $e_details['date'] = date('Y-m-d H:i:s', $row[PlogFeature::COLUMN_DATE]);
                    $e_details['timestamp'] = $row[PlogFeature::COLUMN_DATE];
                    $e_details['uuid'] = $row[PlogFeature::COLUMN_EVENT_UUID];
                    $e_details['image'] = $row[PlogFeature::COLUMN_IMAGE_UUID];
                    $e_details['previewType'] = $row[PlogFeature::COLUMN_PREVIEW];

                    $domophone = json_decode($row[PlogFeature::COLUMN_DOMOPHONE]);

                    if (isset($domophone->domophone_id) && isset($domophone->domophone_output)) {
                        $e_details['objectId'] = strval($domophone->domophone_id);
                        $e_details['objectType'] = "0";
                        $e_details['objectMechanizma'] = strval($domophone->domophone_output);
                        $e_details['mechanizmaDescription'] = isset($domophone->domophone_description) ? $domophone->domophone_description : '';

                        if ($row[PlogFeature::COLUMN_DATE] > time() - 7 * 86400) {
                            $intercom = DeviceIntercom::findById($domophone->domophone_id);

                            if ($intercom) {
                                $entrances = $intercom->entrances()->fetchAll(criteria()->equal('domophone_output', $domophone->domophone_output));

                                if (count($entrances) > 0) {
                                    $e_details['cameraId'] = $entrances[0]->camera_id;
                                }
                            }
                        }
                    }

                    $event_type = (int)$row[PlogFeature::COLUMN_EVENT];
                    $e_details['event'] = strval($event_type);
                    $face = json_decode($row[PlogFeature::COLUMN_FACE], false);

                    if (isset($face->width) && $face->width > 0 && isset($face->height) && $face->height > 0) {
                        $e_details['detailX']['face'] = ['left' => $face->left, 'top' => $face->top, 'width' => $face->width, 'height' => $face->height];

                        $face_id = null;

                        if (isset($face->faceId) && $face->faceId > 0) {
                            $face_id = $face->faceId;
                        }

                        $subscriber_id = (int)$user['subscriberId'];

                        if ($frsFeature->isLikedFlag($request->flatId, $subscriber_id, $face_id, $row[PlogFeature::COLUMN_EVENT_UUID], $flat_owner)) {
                            $e_details['detailX']['flags'] = [FrsFeature::FLAG_LIKED, FrsFeature::FLAG_CAN_DISLIKE];
                        } else {
                            $e_details['detailX']['flags'] = [FrsFeature::FLAG_CAN_LIKE];
                        }
                    }

                    $e_details['detailX']['faceId'] = isset($face->faceId) && $face->faceId > 0 ? strval($face->faceId) : null;

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
                            if ($phones->user_phone) {
                                $e_details['detailX']['phone'] = strval($phones->user_phone);
                            }

                            break;

                        case PlogFeature::EVENT_OPENED_BY_FACE:
                            break;

                        case PlogFeature::EVENT_OPENED_BY_CODE:
                            $e_details['detailX']['code'] = strval($row[PlogFeature::COLUMN_CODE]);

                            break;

                        case PlogFeature::EVENT_OPENED_GATES_BY_CALL:
                            if ($phones->user_phone) {
                                $e_details['detailX']['phoneFrom'] = strval($phones->user_phone);
                            }

                            if ($phones->gate_phone) {
                                $e_details['detailX']['phoneTo'] = strval($phones->gate_phone);
                            }

                            break;
                    }

                    if ((int)$row[PlogFeature::COLUMN_PREVIEW] !== 0) {
                        $img_uuid = $row[PlogFeature::COLUMN_IMAGE_UUID];
                        $url = config_get('api.mobile') . ('/address/plogCamshot/' . $img_uuid);
                        $e_details['preview'] = $url;
                    }

                    $events_details[] = $e_details;
                }

                return user_response(data: $events_details);
            }

            return user_response(data: []);
        } catch (Throwable $throwable) {
            file_logger('plog')->debug($throwable);

            return user_response(500);
        }
    }

    #[Get('/plogCamshot/{uuid}')]
    public function camshot(string $uuid, FileFeature $feature): Response
    {
        try {
            $file = $feature->getFile($feature->fromGUIDv4($uuid), FileStorage::Screenshot);

            return response()
                ->withHeader('Content-Type', 'image/jpeg')
                ->withBody($file->stream);
        } catch (Throwable) {
            return user_response(404, message: 'Скриншота устарел');
        }
    }

    #[Post(
        '/plogDays',
        includes: [
            FlatMiddleware::class => ['flat' => 'flatId'],
            BlockFlatMiddleware::class => ['code' => 200, 'body' => ['code' => 200, 'name' => 'OK', 'data' => []], 'flat' => 'flatId', 'services' => [BlockFeature::SERVICE_INTERCOM, BlockFeature::SUB_SERVICE_EVENT]]
        ]
    )]
    public function days(PlogDaysRequest $request, HouseFeature $houseFeature, PlogFeature $plogFeature): Response
    {
        $user = $this->getUser()->getOriginalValue();

        $flat_details = $houseFeature->getFlat($request->flatId);
        $plog_access = $flat_details['plog'];

        if ($plog_access == PlogFeature::ACCESS_DENIED) {
            return user_response(data: []);
        }

        if ($plog_access == PlogFeature::ACCESS_OWNER_ONLY) {
            foreach ($user['flats'] as $flat)
                if ($flat['flatId'] == $request->flatId) {
                    if ($flat['role'] !== 0) {
                        return user_response(data: []);
                    }

                    break;
                }
        }

        $filter_events = false;

        if ($request->events) {
            $filter_events = explode(',', $request->events);
            $t = [];

            foreach ($filter_events as $e)
                $t[(int)$e] = 1;

            $filter_events = array_keys($t);

            sort($filter_events);

            $filter_events = implode(',', $filter_events);
        }

        try {
            $result = $plogFeature->getEventsDays($request->flatId, $filter_events);

            if ($result) {
                return user_response(data: $result);
            }

            return user_response(data: []);
        } catch (Throwable $throwable) {
            file_logger('plog')->debug($throwable);

            return user_response(500);
        }
    }
}