<?php

namespace Selpol\Feature\Frs\Internal;

use Psr\Log\LoggerInterface;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;

readonly class InternalFrsFeature extends FrsFeature
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = file_logger('frs');
    }

    private function camshotUrl(array $cam): string
    {
        return config_get('api.private') . '/frs/camshot/' . $cam[self::CAMERA_ID];
    }

    private function callback(array $cam): string
    {
        return config_get('api.private') . '/frs/callback?stream_id=' . $cam[self::CAMERA_ID];
    }

    private function addFace($data, $event_uuid)
    {
        $r = $this->getDatabase()->get("select face_id from frs_faces where face_id = :face_id", [":face_id" => $data[self::P_FACE_ID]], options: [self::PDO_SINGLIFY]);

        if ($r)
            return $data[self::P_FACE_ID];

        $content_type = "image/jpeg";
        $image_data = file_get_contents($data[self::P_FACE_IMAGE]);

        if (str_starts_with($data[self::P_FACE_IMAGE], "data:")) {
            if (preg_match_all("/^data:(.*);/i", $image_data, $matches))
                $content_type = end($matches[1]);
        } else {
            $headers = implode("\n", $http_response_header);

            if (preg_match_all("/^content-type\s*:\s*(.*)$/mi", $headers, $matches))
                $content_type = end($matches[1]);
        }

        $file = container(FileFeature::class);

        $face_uuid = $file->toGUIDv4($file->addFile("face_image", temp_stream($image_data), ["contentType" => $content_type, "faceId" => $data[self::P_FACE_ID]]));

        $this->getDatabase()->insert(
            "insert into frs_faces(face_id, face_uuid, event_uuid) values(:face_id, :face_uuid, :event_uuid)",
            [":face_id" => $data[self::P_FACE_ID], ":face_uuid" => $face_uuid, ":event_uuid" => $event_uuid]
        );

        return $data[self::P_FACE_ID];
    }

    public function servers(): array
    {
        return FrsServer::fetchAll();
    }

    public function apiCall(string $base_url, string $method, array $params = []): array|bool
    {
        $l = strlen($base_url);

        if ($l <= 1)
            return false;

        if ($base_url[$l - 1] !== "/")
            $base_url .= "/";

        $l = strlen($method);

        if ($l > 0 && $method[0] === "/")
            $method = substr($method, 1);

        $api_url = $base_url . "api/" . $method;
        $curl = curl_init();

        $this->logger->debug('ApiCall Request', [$params]);

        $data = json_encode($params);
        $options = [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => ['Expect:', 'Accept: application/json', 'Content-Type: application/json']
        ];

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        curl_close($curl);

        $this->logger->debug('ApiCall Response', ['code' => $response_code, 'data' => $response]);

        if ($response_code > self::R_CODE_OK && !$response)
            return ["code" => $response_code];
        else
            return json_decode($response, true);
    }

    public function addStream(string $url, int $cameraId): array
    {
        $method_params = [
            self::P_STREAM_ID => $cameraId,
            self::P_URL => $this->camshotUrl([self::CAMERA_ID => $cameraId]),
            self::P_CALLBACK_URL => $this->callback([self::CAMERA_ID => $cameraId])
        ];

        return $this->apiCall($url, self::M_ADD_STREAM, $method_params);
    }

    public function removeStream(string $url, int $cameraId): array
    {
        $method_params = [self::P_STREAM_ID => $cameraId];

        return $this->apiCall($url, self::M_REMOVE_STREAM, $method_params);
    }

    public function bestQualityByDate(array $cam, ?int $date, string $event_uuid = ''): array|bool|null
    {
        $method_params = [self::P_STREAM_ID => $cam[self::CAMERA_ID], self::P_DATE => date('Y-m-d H:i:s', $date)];

        if ($event_uuid)
            $method_params[self::P_EVENT_UUID] = $event_uuid;

        return $this->apiCall($this->getFrsServerByCamera($cam)->url, self::M_BEST_QUALITY, $method_params);
    }

    public function bestQualityByEventId(array $cam, int $event_id, string $event_uuid = ''): array|bool|null
    {
        $method_params = [self::P_STREAM_ID => $cam[self::CAMERA_ID], self::P_EVENT_ID => $event_id];

        if ($event_uuid)
            $method_params[self::P_EVENT_UUID] = $event_uuid;

        return $this->apiCall($this->getFrsServerByCamera($cam)->url, self::M_BEST_QUALITY, $method_params);
    }

    public function registerFace(array $cam, string $event_uuid, int $left = 0, int $top = 0, int $width = 0, int $height = 0): array|bool
    {
        $event_data = container(PlogFeature::class)->getEventDetails($event_uuid);

        if (!$event_data)
            return false;

        $method_params = [self::P_STREAM_ID => $cam[self::CAMERA_ID], self::P_URL => config_get('api.private') . '/frs/face/' . $event_data['image_uuid']];

        if ($width > 0 && $height > 0) {
            $method_params[self::P_FACE_LEFT] = $left;
            $method_params[self::P_FACE_TOP] = $top;
            $method_params[self::P_FACE_WIDTH] = $width;
            $method_params[self::P_FACE_HEIGHT] = $height;
        }

        $response = $this->apiCall($this->getFrsServerByCamera($cam)->url, self::M_REGISTER_FACE, $method_params);

        if ($response && $response[self::P_CODE] == self::R_CODE_OK && $response[self::P_DATA])
            return [self::P_FACE_ID => $this->addFace($response[self::P_DATA], $event_uuid)];

        return $response;
    }

    public function removeFaces(array $cam, array $faces): array|bool|null
    {
        $method_params = [self::P_STREAM_ID => $cam[self::CAMERA_ID], self::P_FACE_IDS => $faces];

        return $this->apiCall($this->getFrsServerByCamera($cam)->url, self::M_REMOVE_FACES, $method_params);
    }

    public function motionDetection(array $cam, bool $is_start): array|bool|null
    {
        $method_params = [self::P_STREAM_ID => $cam[self::CAMERA_ID], self::P_START => $is_start];

        return $this->apiCall($this->getFrsServerByCamera($cam)->url, self::M_MOTION_DETECTION, $method_params);
    }

    public function cron($part): bool
    {
        if ($part === config_get('feature.frs.cron_sync_data_scheduler'))
            $this->syncData();

        return true;
    }

    private function deleteFaceId(int $face_id): void
    {
        $this->getDatabase()->modify("delete from frs_links_faces where face_id = :face_id", [":face_id" => $face_id]);

        $r = $this->getDatabase()->get("select face_uuid from frs_faces where face_id = :face_id", [":face_id" => $face_id], [], [self::PDO_SINGLIFY]);

        if ($r) {
            $file = container(FileFeature::class);

            $file->deleteFile($file->fromGUIDv4($r["face_uuid"]));
        }

        $this->getDatabase()->modify("delete from frs_faces where face_id = :face_id", [":face_id" => $face_id]);
    }

    private function syncData(): void
    {
        if (!is_array($this->servers())) {
            $this->logger->debug('syncData() skip');

            return;
        }

        $this->logger->debug('syncData() process', ['server' => $this->servers()]);

        if (count($this->servers()) === 0)
            return;

        //syncing all faces
        $frs_all_faces = [];

        foreach ($this->servers() as $frs_server) {
            $all_faces = $this->apiCall($frs_server->url, self::M_LIST_ALL_FACES);

            if ($all_faces && array_key_exists(self::P_DATA, $all_faces)) {
                $frs_all_faces = array_merge($frs_all_faces, $all_faces[self::P_DATA]);
            }
        }

        $rbt_all_faces = [];

        foreach ($this->getDatabase()->get("select face_id from frs_faces order by 1") as $row)
            $rbt_all_faces[] = $row["face_id"];

        $this->logger->debug('syncData() faces', ['frs' => count($frs_all_faces), 'rbt' => count($rbt_all_faces)]);

        $diff_faces = array_diff($rbt_all_faces, $frs_all_faces);

        if ($diff_faces) {
            $this->logger->debug('syncData() rbt add faces', ['count' => count($diff_faces)]);

            foreach ($diff_faces as $f_id) {
                $r = $this->getDatabase()->get("select face_uuid from frs_faces where face_id = :face_id", [":face_id" => $f_id], [], [self::PDO_SINGLIFY]);

                if ($r) {
                    $file = container(FileFeature::class);

                    $file->deleteFile($file->fromGUIDv4($r["face_uuid"]));
                }
            }

            $this->getDatabase()->modify("delete from frs_links_faces where face_id in (" . implode(",", $diff_faces) . ")");
            $this->getDatabase()->modify("delete from frs_faces where face_id in (" . implode(",", $diff_faces) . ")");
        }

        if (count($diff_faces) > 0)
            $this->logger->debug('syncData() remove faces from rbt', ['count' => count($diff_faces)]);

        $diff_faces = array_diff($frs_all_faces, $rbt_all_faces);

        if ($diff_faces) {
            $this->logger->debug('syncData() rbt remove faces', ['count' => count($diff_faces)]);

            foreach ($this->servers() as $frs_server) {
                $this->apiCall($frs_server->url, self::M_DELETE_FACES, [self::P_FACE_IDS => $diff_faces]);
            }
        }

        if (count($diff_faces) > 0)
            $this->logger->debug('syncData() remove faces from frs', ['count' => count($diff_faces)]);

        $frs_all_data = [];

        foreach ($this->servers() as $frs_server) {
            $frs_all_data[$frs_server->url] = [];
            $streams = $this->apiCall($frs_server->url, self::M_LIST_STREAMS);

            if ($streams && isset($streams[self::P_DATA]) && is_array($streams[self::P_DATA]))
                foreach ($streams[self::P_DATA] as $item) {
                    if (array_key_exists(self::P_FACE_IDS, $item))
                        $frs_all_data[$frs_server->url][$item[self::P_STREAM_ID]] = $item[self::P_FACE_IDS];
                    else
                        $frs_all_data[$frs_server->url][$item[self::P_STREAM_ID]] = [];
                }
        }

        $rbt_all_data = [];

        /** @var FrsServer[] $frsServers */
        $frsServers = [];

        $rbt_data = $this->getDatabase()->get("select c.frs_server_id, c.camera_id from cameras c where c.frs_server_id is not null order by 1, 2");

        if (is_array($rbt_data))
            foreach ($rbt_data as $item) {
                if (!array_key_exists($item['frs_server_id'], $frsServers))
                    $frsServers[$item['frs_server_id']] = FrsServer::findById($item['frs_server_id'], setting: setting()->columns(['url'])->nonNullable());

                $frs_base_url = $frsServers[$item['frs_server_id']]->url;
                $stream_id = $item['camera_id'];
                $rbt_all_data[$frs_base_url][$stream_id] = [];
            }

        $rbt_data = $this->getDatabase()->get("select distinct c.frs_server_id, c.camera_id, flf.face_id, ff.face_uuid
                    from frs_links_faces flf
                      left join frs_faces ff on flf.face_id = ff.face_id
                      inner join houses_entrances_flats hef on hef.house_flat_id = flf.flat_id
                      inner join houses_entrances he on hef.house_entrance_id = he.house_entrance_id
                      inner join cameras c on he.camera_id = c.camera_id
                    where c.frs_server_id is not null");

        if (is_array($rbt_data))
            foreach ($rbt_data as $item) {
                if (!array_key_exists($item['frs_server_id'], $frsServers))
                    $frsServers[$item['frs_server_id']] = FrsServer::findById($item['frs_server_id'], setting: setting()->columns(['url'])->nonNullable());

                $frs_base_url = $frsServers[$item['frs_server_id']]->url;
                $stream_id = $item['camera_id'];
                $face_id = $item['face_id'];
                $face_uuid = $item['face_uuid'];

                if ($face_uuid === null) {
                    //face image doesn't exist in th RBT, so delete it everywhere
                    $this->deleteFaceId($face_id);
                    $this->apiCall($frs_base_url, self::M_DELETE_FACES, [$face_id]);
                } else {
                    $rbt_all_data[$frs_base_url][$stream_id][] = $face_id;
                }
            }

        foreach ($rbt_all_data as $base_url => $data) {
            //syncing video streams
            $diff_streams = array_diff_key($data, $frs_all_data[$base_url]);

            if (count($diff_streams) > 0)
                $this->logger->debug('syncData() add streams to frs', ['diff' => $diff_streams]);

            foreach ($diff_streams as $stream_id => $faces) {
                $cam = container(CameraFeature::class)->getCamera($stream_id);

                if ($cam) {
                    $method_params = [
                        self::P_STREAM_ID => $stream_id,
                        self::P_URL => $this->camshotUrl($cam),
                        self::P_CALLBACK_URL => $this->callback($cam)
                    ];
                    if ($faces) {
                        $method_params[self::P_FACE_IDS] = $faces;
                    }
                    $this->apiCall($base_url, self::M_ADD_STREAM, $method_params);
                }
            }

            $diff_streams = array_diff_key($frs_all_data[$base_url], $data);

            if (count($diff_streams) > 0)
                $this->logger->debug('syncData() remove streams from frs', ['diff' => $diff_streams]);

            foreach (array_keys($diff_streams) as $stream_id) {
                $this->apiCall($base_url, self::M_REMOVE_STREAM, [self::P_STREAM_ID => $stream_id]);
            }

            //syncing faces
            $common_streams = array_intersect_key($data, $frs_all_data[$base_url]);
            foreach ($common_streams as $stream_id => $rbt_faces) {
                $diff_faces = array_diff($rbt_faces, $frs_all_data[$base_url][$stream_id]);

                if (count($diff_faces) > 0)
                    $this->logger->debug('syncData() add faces to frs', ['diff' => $diff_faces]);

                if ($diff_faces) {
                    $this->apiCall($base_url, self::M_ADD_FACES, [self::P_STREAM_ID => $stream_id, self::P_FACE_IDS => $diff_faces]);
                }

                $diff_faces = array_diff($frs_all_data[$base_url][$stream_id], $rbt_faces);

                if (count($diff_faces) > 0)
                    $this->logger->debug('syncData() remove faces from frs', ['diff' => $diff_faces]);

                if ($diff_faces)
                    $this->apiCall($base_url, self::M_REMOVE_FACES, [self::P_STREAM_ID => $stream_id, self::P_FACE_IDS => $diff_faces]);
            }
        }
    }

    public function attachFaceId(int $face_id, int $flat_id, int $house_subscriber_id): bool|int|string
    {
        $r = $this->getDatabase()->get(
            "select face_id from frs_links_faces where flat_id = :flat_id and house_subscriber_id = :house_subscriber_id and face_id = :face_id",
            [":flat_id" => $flat_id, ":house_subscriber_id" => $house_subscriber_id, ":face_id" => $face_id],
            options: [self::PDO_SINGLIFY]
        );

        if ($r)
            return true;

        return $this->getDatabase()->insert(
            "insert into frs_links_faces(face_id, flat_id, house_subscriber_id) values(:face_id, :flat_id, :house_subscriber_id)",
            [":face_id" => $face_id, ":house_subscriber_id" => $house_subscriber_id, ":flat_id" => $flat_id]
        );
    }

    public function detachFaceId(int $face_id, int $house_subscriber_id): bool|int
    {
        $r = $this->getDatabase()->get(
            "select flat_id from frs_links_faces where face_id = :face_id and house_subscriber_id = :house_subscriber_id",
            [":face_id" => $face_id, ":house_subscriber_id" => $house_subscriber_id],
            options: [self::PDO_SINGLIFY]
        );

        if (!$r)
            return false;

        $flat_id = $r["flat_id"];

        $r = $this->getDatabase()->modify("delete from frs_links_faces where face_id = :face_id and house_subscriber_id = :house_subscriber_id", [":face_id" => $face_id, ":house_subscriber_id" => $house_subscriber_id]);

        $households = container(HouseFeature::class);
        $entrances = $households->getEntrances("flatId", $flat_id);

        foreach ($entrances as $entrance) {
            $cam = container(CameraFeature::class)->getCamera($entrance["cameraId"]);

            $this->removeFaces($cam, [$face_id]);
        }

        return $r;
    }

    public function detachFaceIdFromFlat(int $face_id, int $flat_id): bool|int
    {
        return $this->getDatabase()->modify("delete from frs_links_faces where face_id = :face_id and flat_id = :flat_id", [":face_id" => $face_id, ":flat_id" => $flat_id]);
    }

    public function getEntranceByCameraId(int $camera_id): array|bool
    {
        $r = $this->getDatabase()->get("select he.house_entrance_id from houses_entrances he where he.camera_id = " . $camera_id . " limit 1", [], ["house_entrance_id" => "entranceId"]);

        if (count($r) == 1) {
            $households = container(HouseFeature::class);

            return $households->getEntrance($r[0]["entranceId"]);
        }

        return false;
    }

    public function getFlatsByFaceId(int $face_id, int $entrance_id): array
    {
        $r = $this->getDatabase()->get("
                    select flf.flat_id
                    from houses_entrances_flats hef inner join frs_links_faces flf on hef.house_flat_id = flf.flat_id
                    where hef.house_entrance_id = :entrance_id and flf.face_id = :face_id
                ", [":entrance_id" => $entrance_id, ":face_id" => $face_id,], ["flat_id" => "flatId"]);

        $result = [];

        if ($r)
            foreach ($r as $row)
                $result[] = $row["flatId"];

        return $result;
    }

    public function isLikedFlag(int $flat_id, int $subscriber_id, ?int $face_id, ?string $event_uuid, bool $is_owner): bool
    {
        $is_liked1 = false;

        if ($event_uuid !== null) {
            $r = $this->getDatabase()->get("select face_id from frs_faces where event_uuid = :event_uuid", [":event_uuid" => $event_uuid], [], [self::PDO_SINGLIFY]);

            if ($r) {
                $registered_face_id = $r["face_id"];
                $query = "select face_id from frs_links_faces where flat_id = " . $flat_id . " and face_id = " . $registered_face_id;

                if (!$is_owner)
                    $query .= " and house_subscriber_id = " . $subscriber_id;

                $is_liked1 = count($this->getDatabase()->get($query)) > 0;
            }
        }

        $is_liked2 = false;

        if ($face_id !== null) {
            $query = "select face_id from frs_links_faces where flat_id = " . $flat_id . " and face_id = " . $face_id;

            if (!$is_owner)
                $query .= " and house_subscriber_id = " . $subscriber_id;

            $is_liked2 = count($this->getDatabase()->get($query)) > 0;
        }

        return $is_owner && $is_liked1 || $is_liked2;
    }

    public function listFaces(int $flat_id, int $subscriber_id, bool $is_owner = false): array
    {
        if ($is_owner) {
            $r = $this->getDatabase()->get("
                    select ff.face_id, ff.face_uuid
                    from frs_links_faces lf inner join frs_faces ff on lf.face_id = ff.face_id
                    where lf.flat_id = :flat_id
                    order by ff.face_id
                ", [":flat_id" => $flat_id]);
        } else {
            $r = $this->getDatabase()->get("
                    select ff.face_id, ff.face_uuid
                    from frs_links_faces lf inner join frs_faces ff on lf.face_id = ff.face_id
                    where lf.flat_id = :flat_id and lf.house_subscriber_id = :subscriber_id
                    order by ff.face_id
                ", [":flat_id" => $flat_id, ":subscriber_id" => $subscriber_id]);
        }

        $list_faces = [];

        foreach ($r as $row)
            $list_faces[] = [self::P_FACE_ID => $row['face_id'], self::P_FACE_IMAGE => $row['face_uuid']];

        return $list_faces;
    }

    public function getRegisteredFaceId(string $event_uuid): int|bool
    {
        $r = $this->getDatabase()->get("select face_id from frs_faces where event_uuid = :event_uuid", [":event_uuid" => $event_uuid], [], [self::PDO_SINGLIFY]);

        if ($r)
            return $r["face_id"];

        return false;
    }

    private function getFrsServerByCamera(array $camera): FrsServer
    {
        return FrsServer::findById($camera[self::CAMERA_FRS_SERVER_ID], setting: setting()->nonNullable());
    }
}