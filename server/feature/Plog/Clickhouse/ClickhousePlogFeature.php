<?php

namespace Selpol\Feature\Plog\Clickhouse;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\File\File;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileMetadata;
use Selpol\Feature\File\FileStorage;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Feature\Schedule\ScheduleTimeInterface;
use Selpol\Framework\Entity\Database\EntityStatementInterface;
use Selpol\Service\ClickhouseService;
use Selpol\Task\Tasks\Plog\PlogCallTask;
use Selpol\Task\Tasks\Plog\PlogOpenTask;
use Throwable;

readonly class ClickhousePlogFeature extends PlogFeature
{
    private ClickhouseService $clickhouse;

    private int $ttl_camshot_days;  // время жизни кадра события
    private int $back_time_shift_video_shot;  // сдвиг назад в секундах от времени события для получения кадра от медиа сервера

    public function __construct()
    {
        $this->clickhouse = container(ClickhouseService::class);

        $plog = config_get('feature.plog');

        $this->ttl_camshot_days = intval($plog['ttl_camshot_days']);
        $this->back_time_shift_video_shot = $plog['back_time_shift_video_shot'];
    }

    public function cron(ScheduleTimeInterface $value): bool
    {
        if (!$value->daily()) {
            return true;
        }

        $database = $this->clickhouse->database;

        return $this->clickhouse->statement("ALTER TABLE $database.plog UPDATE hidden = 1 WHERE hidden = 0 AND date <= now() - :date")
            ->bind('date', time() - $this->ttl_camshot_days * 86400)
            ->execute();
    }

    /**
     * Получение кадра события на указанную дату+время и ip устройства или от FRS
     * @inheritDoc
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getCamshot(int $domophone_id, int $door_id, string|bool|null $date, int|bool|null $event_id = false): array
    {
        $fileFeature = container(FileFeature::class);

        $camshot_data = [];

        $households = container(HouseFeature::class);

        $entrances = $households->getEntrances("domophoneId", ["domophoneId" => $domophone_id, "output" => $door_id]);

        if ($entrances && $entrances[0]) {
            $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);

            if ($cameras && $cameras[0]) {
                if ($event_id === false) {
                    $response = container(FrsFeature::class)->bestQualityByDate($cameras[0], $date);
                } else {
                    $response = container(FrsFeature::class)->bestQualityByEventId($cameras[0], $event_id);
                }

                file_logger('plog')->debug('frs response', ['response' => $response]);

                if ($response && $response[FrsFeature::P_CODE] == FrsFeature::R_CODE_OK && $response[FrsFeature::P_DATA]) {
                    $image_data = file_get_contents($response[FrsFeature::P_DATA][FrsFeature::P_SCREENSHOT]);
                    if ($image_data) {
                        $headers = implode("\n", $http_response_header);
                        $contentType = "image/jpeg";

                        if (preg_match_all("/^content-type\s*:\s*(.*)$/mi", $headers, $matches)) {
                            $contentType = end($matches[1]);
                        }

                        $camshot_data[self::COLUMN_IMAGE_UUID] = $fileFeature->toGUIDv4(
                            $fileFeature->addFile(
                                File::stream(stream($image_data))
                                    ->withFilename('screenshot')
                                    ->withMetadata(FileMetadata::contentType($contentType)->withExpire(time() + $this->ttl_camshot_days * 86400)),
                                FileStorage::Screenshot
                            )
                        );
                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_FRS;
                        $camshot_data[self::COLUMN_FACE] = [
                            FrsFeature::P_FACE_LEFT => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_LEFT],
                            FrsFeature::P_FACE_TOP => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_TOP],
                            FrsFeature::P_FACE_WIDTH => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_WIDTH],
                            FrsFeature::P_FACE_HEIGHT => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_HEIGHT],
                        ];
                    }
                }

                file_logger('plog')->debug('frs camshot', ['data' => $camshot_data]);

                if (!$camshot_data) {
                    if ($cameras[0]['dvrServerId']) {
                        $ts_event = $date - $this->back_time_shift_video_shot;
                        $filename = "/tmp/" . uniqid('camshot_') . ".jpeg";

                        $urlOfScreenshot = container(DvrFeature::class)->getUrlOfScreenshot($cameras[0], $ts_event, true);

                        file_logger('plog')->debug('dvr camshot', ['url' => $urlOfScreenshot]);

                        if (str_contains($urlOfScreenshot, '.mp4')) {
                            shell_exec("ffmpeg -y -i " . $urlOfScreenshot . " -vframes 1 $filename 1>/dev/null 2>/dev/null");
                        } else {
                            file_put_contents($filename, file_get_contents($urlOfScreenshot));
                        }

                        if (file_exists($filename)) {
                            $camshot_data[self::COLUMN_IMAGE_UUID] = $fileFeature->toGUIDv4(
                                $fileFeature->addFile(
                                    File::stream(stream(fopen($filename, 'rb')))
                                        ->withFilename('screenshot')
                                        ->withMetadata(FileMetadata::contentType('image/jpeg')->withExpire(time() + $this->ttl_camshot_days * 86400)),
                                    FileStorage::Screenshot
                                )
                            );

                            unlink($filename);

                            $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_DVR;
                        } else {
                            $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                        }
                    } else {
                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                    }

                    file_logger('plog')->debug('dvr camshot', ['data' => $camshot_data]);
                }
            }
        }

        return $camshot_data;
    }

    public function getSegment(int $domophone_id, int $door_id, int $date): ?string
    {
        if ($date < time() - 7 * 86400) {
            return null;
        }

        $intercom = DeviceIntercom::findById($domophone_id);

        if (!$intercom) {
            return null;
        }

        $entrances = $intercom->entrances()->fetchAll(criteria()->equal('domophone_output', $door_id));

        if (count($entrances) == 0) {
            return null;
        }

        $entrance = $entrances[0];

        $dvr = dvr($entrance->camera->dvr_server_id);

        $video = $dvr->segment($dvr->identifier($entrance->camera, $date, null), $entrance->camera, $date - 2, $date + 2);

        return $video?->value->src;
    }

    /**
     * @inheritDoc
     * @throws NotFoundExceptionInterface
     */
    public function writeEventData(array $event_data, array $flat_list = []): void
    {
        if (count($flat_list)) {
            foreach ($flat_list as $flat_id) {
                $hidden = $this->getPlogHidden($flat_id);
                if ($hidden < 0) {
                    continue;
                }
                $event_data[self::COLUMN_HIDDEN] = $hidden;
                $event_data[self::COLUMN_FLAT_ID] = $flat_id;

                $statement = $this->getInsertStatement($event_data);

                if (!$statement->execute()) {
                    file_logger('plog')->error('Error writeEventData', $statement->error());
                }
            }
        } else {
            $hidden = $this->getPlogHidden($event_data[self::COLUMN_FLAT_ID]);

            if ($hidden < 0) {
                return;
            }

            $event_data[self::COLUMN_HIDDEN] = $hidden;

            $statement = $this->getInsertStatement($event_data);

            if (!$statement->execute()) {
                file_logger('plog')->error('Error writeEventData', $statement->error());
            }
        }
    }

    private function getInsertStatement(array $data): EntityStatementInterface
    {
        $query = 'INSERT INTO prod.plog(`date`, `event_uuid`, `hidden`, `image_uuid`, `flat_id`, `domophone`, `event`, `opened`, `face`, `rfid`, `code`, `phones`, `preview`) VALUES (';

        $query .= $data['date'] . ', ';
        $query .= "'" . $data['event_uuid'] . "', ";
        $query .= $data['hidden'] . ', ';
        $query .= "'" . $data['image_uuid'] . "', ";
        $query .= $data['flat_id'] . ', ';
        $query .= "tuple('" . $data['domophone']['domophone_description'] . "', " . $data['domophone']['domophone_id'] . ', ' . $data['domophone']['domophone_output'] . '), ';
        $query .= $data['event'] . ', ';
        $query .= $data['opened'] . ', ';
        $query .= "tuple('" . $data['face']['faceId'] . "', " . ($data['face']['height'] ?? 0) . ', ' . ($data['face']['left'] ?? 0) . ', ' . ($data['face']['top'] ?? 0) . ', ' . ($data['face']['width'] ?? 0) . '), ';
        $query .= "'" . $data['rfid'] . "', ";
        $query .= "'" . $data['code'] . "', ";
        $query .= 'tuple(' . ($data['phones']['user_phone'] == '' ? 'NULL' : $data['phones']['user_phone']) . '), ';
        $query .= "'" . $data['preview'] . "'";

        $query .= ')';

        return container(ClickhouseService::class)->statement($query);
    }

    /**
     * @inheritDoc
     */
    public function addCallDoneData(int $date, string $ip, ?int $call_id): void
    {
        try {
            task(new PlogCallTask($this->getDomophoneId($ip), $ip, $date, $call_id))->delay(15)->default()->async();
        } catch (Throwable $e) {
            file_logger('task')->error('Error addCallDoneData' . PHP_EOL . $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function addDoorOpenData(int $date, string $ip, int $event_type, int $door, string $detail): void
    {
        try {
            task(new PlogOpenTask($this->getDomophoneId($ip), $event_type, $door, $date, $detail))->delay(15)->default()->async();
        } catch (Throwable $e) {
            file_logger('task')->error('Error addDoorOpenData' . PHP_EOL . $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function addDoorOpenDataById(int $date, int $domophone_id, int $event_type, int $door, string $detail): void
    {
        try {
            task(new PlogOpenTask($domophone_id, $event_type, $door, $date, $detail))->delay(15)->default()->async();
        } catch (Throwable $e) {
            file_logger('task')->error('Error addDoorOpenDataById' . PHP_EOL . $e);
        }
    }

    public function getSyslog(string $ip, int $date, int $max_call_time): false|array
    {
        $database = $this->clickhouse->database;

        $start_date = $date - $max_call_time;
        $query = "select date, msg, unit from $database.syslog s where IPv4NumToString(s.ip) = '$ip' and s.date > $start_date and s.date <= $date order by date desc";

        return $this->clickhouse->select($query);
    }

    public function getSyslogFilter(string $ip, ?string $message, ?int $minDate, ?int $maxDate, ?int $page, ?int $size): false|array
    {
        $database = $this->clickhouse->database;

        $query = "SELECT date, msg FROM $database.syslog s WHERE IPv4NumToString(s.ip) = '$ip'";

        if ($message)
            $query .= ' AND msg LIKE \'%' . $message . '%\'';

        if ($minDate && $maxDate)
            $query .= ' AND date BETWEEN ' . $minDate . ' AND ' . $maxDate;
        else if ($minDate)
            $query .= ' AND date >= ' . $minDate;
        else if ($maxDate)
            $query .= ' AND date <= ' . $maxDate;

        $query .= ' ORDER BY date DESC';

        if ($page !== null && $size && $size > 0)
            $query .= ' LIMIT ' . $size . ' OFFSET ' . ($page * $size);

        return $this->clickhouse->select($query);
    }

    public function getEventsDays(int $flat_id, ?string $filter_events): array|bool
    {
        $database = $this->clickhouse->database;

        $date = time() - $this->ttl_camshot_days * 86400;

        if ($filter_events) {
            $query = "
                        select
                            toYYYYMMDD(FROM_UNIXTIME(date)) as day,
                            count(day) as events
                        from
                            $database.plog
                        where
                            not hidden
                            and flat_id = $flat_id
                            and event in ($filter_events)
                            and date >= $date
                        group by
                            day
                        order by
                            day desc
                    ";
        } else {
            $query = "
                        select
                            toYYYYMMDD(FROM_UNIXTIME(date)) as day,
                            count(day) as events
                        from
                            $database.plog
                        where
                            not hidden
                            and flat_id = $flat_id
                            and date >= $date
                        group by
                            day
                        order by
                            day desc
                    ";
        }

        $result = $this->clickhouse->select($query);

        if (count($result)) {
            foreach ($result as &$d) {
                $d['day'] = substr($d['day'], 0, 4) . '-' . substr($d['day'], 4, 2) . '-' . substr($d['day'], 6, 2);
            }
            return $result;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDetailEventsByDay(int $flat_id, string $date): array|bool
    {
        $database = $this->clickhouse->database;

        $query = "
                    select
                        date,
                        event_uuid,
                        hidden,
                        image_uuid,
                        flat_id,
                        toJSONString(domophone) domophone,
                        event,
                        opened,
                        toJSONString(face) face,
                        rfid,
                        code,
                        toJSONString(phones) phones,
                        preview
                    from
                        $database.plog
                    where
                        not hidden
                        and toYYYYMMDD(FROM_UNIXTIME(date)) = '$date'
                        and flat_id = $flat_id
                    order by
                        date desc
                ";

        return $this->clickhouse->select($query);
    }

    /**
     * @inheritDoc
     */
    public function getEventsByFlatsAndDomophone(array $flats_id, int $domophone_id, int $date): bool|array
    {
        $database = $this->clickhouse->database;

        $filterFlatsId = implode(',', $flats_id);
        $filterDate = date('Ymd', time() - $date * 24 * 60 * 60);

        $query = "
                    select
                        date
                    from
                        $database.plog
                    where
                        not hidden
                        and toYYYYMMDD(FROM_UNIXTIME(date)) >= '$filterDate'
                        and flat_id in ($filterFlatsId)
                        and tupleElement(domophone, 'domophone_id') = $domophone_id
                    order by
                        date desc
            ";

        return $this->clickhouse->select($query);
    }

    public function getMotionsByHost(string $ip, int $date): bool|array
    {
        $database = $this->clickhouse->database;

        $query = "select start, end from $database.motion where ip = :ip and start >= :start order by start desc";

        $start = time() - $date * 24 * 60 * 60;
        $start -= $start % 86400;

        $statement = $this->clickhouse->statement($query)
            ->bind('ip', $ip)
            ->bind('start', $start);

        if ($statement->execute()) {
            return $statement->fetchAll();
        } else {
            file_logger('clickhouse')->error('Error load motions', $statement->error());

            return false;
        }
    }

    public function getEventByFlatsAndIntercom(array $flatIds, int $intercomId, int $after, int $before): bool|array
    {
        $database = $this->clickhouse->database;

        $filterFlatsId = implode(',', $flatIds);

        $query = "select date, event from $database.plog where not hidden and date between $after and $before and flat_id in ($filterFlatsId) and domophone.domophone_id = $intercomId order by date desc";

        return $this->clickhouse->select($query);
    }

    public function getEventsByIntercom(int $intercomId, int $after, int $before): bool|array
    {
        $database = $this->clickhouse->database;

        $query = "select date, event from $database.plog where not hidden and date between $after and $before and domophone.domophone_id = $intercomId order by date desc";

        return $this->clickhouse->select($query);
    }

    public function getEventsByFlat(int $flatId, ?int $type, ?int $opened, int $page, int $size): bool|array
    {
        $database = $this->clickhouse->database;

        $offset = $page * $size;

        $query = "SELECT * FROM $database.plog WHERE NOT hidden AND flat_id = $flatId";

        if ($type !== null) {
            $query .= " AND event = $type";
        }

        if ($opened !== null) {
            $query .= " AND opened = $opened";
        }

        return $this->clickhouse->select($query . " ORDER BY date DESC LIMIT $size OFFSET $offset");
    }

    public function getEventsByHouse(AddressHouse $house, ?int $type, ?int $opened, int $page, int $size): bool|array
    {
        $entrances = $house->entrances()->fetchAll(setting: setting()->columns(['house_domophone_id']));
        $where = implode(', ', array_map(static fn(HouseEntrance $entrance) => $entrance->house_domophone_id, $entrances));

        $database = $this->clickhouse->database;

        $offset = $page * $size;

        $query = "SELECT * FROM $database.plog WHERE NOT hidden AND domophone.domophone_id in ($where)";

        if ($type !== null) {
            $query .= " AND event = $type";
        }

        if ($opened !== null) {
            $query .= " AND opened = $opened";
        }

        return $this->clickhouse->select($query . " ORDER BY date DESC LIMIT $size OFFSET $offset");
    }

    /**
     * @inheritDoc
     */
    public function getEventDetails(string $uuid): bool|array
    {
        $database = $this->clickhouse->database;

        $query = "
                    select
                        date,
                        event_uuid,
                        hidden,
                        image_uuid,
                        flat_id,
                        toJSONString(domophone) domophone,
                        event,
                        opened,
                        toJSONString(face) face,
                        rfid,
                        code,
                        toJSONString(phones) phones,
                        preview
                    from
                        $database.plog
                    where
                        event_uuid = '$uuid'
                ";

        return $this->clickhouse->select($query)[0];
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getDomophoneId($ip): bool|int
    {
        $intercom = DeviceIntercom::fetch(criteria()->equal('ip', $ip), setting()->columns(['house_domophone_id']));

        if ($intercom) {
            return $intercom->house_domophone_id;
        }

        return false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function getPlogHidden($flat_id): int
    {
        $flat = container(HouseFeature::class)->getFlat($flat_id);

        $hidden = 0;

        if ($flat['plog'] == self::ACCESS_DENIED)
            $hidden = 1;

        return $hidden;
    }
}
