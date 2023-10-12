<?php

namespace Selpol\Feature\Frs;

use Selpol\Feature\Feature;
use Selpol\Feature\Frs\Internal\InternalFrsFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalFrsFeature::class)]
abstract class FrsFeature extends Feature
{
    //FRS params names
    const P_CODE = "code";
    const P_DATA = "data";
    const P_STREAM_ID = "streamId";
    const P_URL = "url";
    const P_FACE_IDS = "faces";
    const P_CALLBACK_URL = "callback";
    const P_START = "start";
    const P_DATE = "date";
    const P_EVENT_ID = "eventId";
    const P_EVENT_UUID = "uuid";
    const P_SCREENSHOT = "screenshot";
    const P_FACE_LEFT = "left";
    const P_FACE_TOP = "top";
    const P_FACE_WIDTH = "width";
    const P_FACE_HEIGHT = "height";
    const P_FACE_ID = "faceId";
    const P_FACE_IMAGE = "faceImage";
    const P_PARAMS = "params";
    const P_PARAM_NAME = "paramName";
    const P_PARAM_VALUE = "paramValue";
    const P_QUALITY = "quality";
    const P_DATE_START = "dateStart";
    const P_DATE_END = "dateEnd";
    const P_MESSAGE = "message";

    //FRS method names
    const M_ADD_STREAM = "addStream";
    const M_BEST_QUALITY = "bestQuality";
    const M_MOTION_DETECTION = "motionDetection";
    const M_REGISTER_FACE = "registerFace";
    const M_REMOVE_FACES = "removeFaces";
    const M_LIST_STREAMS = "listStreams";
    const M_LIST_ALL_FACES = "listAllFaces";
    const M_DELETE_FACES = "deleteFaces";
    const M_REMOVE_STREAM = "removeStream";
    const M_ADD_FACES = "addFaces";

    //response codes
    const R_CODE_OK = 200;

    //internal params names
    const CAMERA_ID = "cameraId";
    const CAMERA_URL = "url";
    const CAMERA_CREDENTIALS = "credentials";
    const CAMERA_FRS = "frs";
    const FRS_BASE_URL = "url";
    const FRS_STREAMS = "streams";
    const FRS_ALL_FACES = "allFaces";
    const FRS_FACES = "faces";

    //other
    const PDO_SINGLIFY = "singlify";
    const PDO_FIELDLIFY = "fieldlify";

    const FLAG_CAN_LIKE = "canLike";
    const FLAG_CAN_DISLIKE = "canDislike";
    const FLAG_LIKED = "liked";

    abstract public function cron(string $part): bool;

    abstract public function servers(): array;

    abstract public function apiCall(string $base_url, string $method, array $params = []): array|bool;

    abstract public function addStream(string $url, int $cameraId): array;

    abstract public function removeStream(string $url, int $cameraId): array;

    abstract public function bestQualityByDate(array $cam, ?int $date, string $event_uuid = ''): array|bool|null;

    abstract public function bestQualityByEventId(array $cam, int $event_id, string $event_uuid = ""): array|bool|null;

    abstract public function registerFace(array $cam, string $event_uuid, int $left = 0, int $top = 0, int $width = 0, int $height = 0): array|bool;

    abstract public function removeFaces(array $cam, array $faces): array|bool|null;

    abstract public function motionDetection(array $cam, bool $is_start): array|bool|null;

    abstract public function attachFaceId(int $face_id, int $flat_id, int $house_subscriber_id): bool|int|string;

    abstract public function detachFaceId(int $face_id, int $house_subscriber_id): bool|int;

    abstract public function detachFaceIdFromFlat(int $face_id, int $flat_id): bool|int;

    abstract public function getEntranceByCameraId(int $camera_id): array|bool;

    abstract public function getFlatsByFaceId(int $face_id, int $entrance_id): array;

    abstract public function isLikedFlag(int $flat_id, int $subscriber_id, int $face_id, string $event_uuid, bool $is_owner): bool;

    abstract public function listFaces(int $flat_id, int $subscriber_id, bool $is_owner = false): array;

    abstract public function getRegisteredFaceId(string $event_uuid): int|bool;
}