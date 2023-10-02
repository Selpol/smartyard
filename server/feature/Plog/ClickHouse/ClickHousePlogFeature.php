<?php

namespace Selpol\Feature\Plog\ClickHouse;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Frs\FrsFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Service\ClickhouseService;
use Selpol\Task\Tasks\Plog\PlogCallTask;
use Selpol\Task\Tasks\Plog\PlogOpenTask;
use Throwable;

class ClickHousePlogFeature extends PlogFeature
{
    private ClickhouseService $clickhouse;

    private int $max_call_length;  // максимальная длительность звонка в секундах
    private int $ttl_camshot_days;  // время жизни кадра события
    private int $back_time_shift_video_shot;  // сдвиг назад в секундах от времени события для получения кадра от медиа сервера

    public function __construct()
    {
        $plog = config('feature.plog');

        $this->clickhouse = new ClickhouseService($plog['host'], $plog['port'], $plog['username'], $plog['password'], $plog['database']);

        $this->max_call_length = $plog['max_call_length'];
        $this->ttl_camshot_days = $plog['ttl_camshot_days'];
        $this->back_time_shift_video_shot = $plog['back_time_shift_video_shot'];
    }

    /**
     * Получение кадра события на указанную дату+время и ip устройства или от FRS
     * @inheritDoc
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getCamshot(int $domophone_id, string|bool|null $date, int|bool|null $event_id = false): array
    {
        $file = container(FileFeature::class);

        $camshot_data = [];

        $households = container(HouseFeature::class);

        $entrances = $households->getEntrances("domophoneId", ["domophoneId" => $domophone_id, "output" => "0"]);

        if ($entrances && $entrances[0]) {
            $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
            if ($cameras && $cameras[0]) {
                if ($event_id === false) {
                    $response = container(FrsFeature::class)->bestQualityByDate($cameras[0], $date);
                } else {
                    $response = container(FrsFeature::class)->bestQualityByEventId($cameras[0], $event_id);
                }

                logger('plog')->debug('frs response', ['response' => $response]);

                if ($response && $response[FrsFeature::P_CODE] == FrsFeature::R_CODE_OK && $response[FrsFeature::P_DATA]) {
                    $image_data = file_get_contents($response[FrsFeature::P_DATA][FrsFeature::P_SCREENSHOT]);
                    if ($image_data) {
                        $headers = implode("\n", $http_response_header);
                        $content_type = "image/jpeg";
                        if (preg_match_all("/^content-type\s*:\s*(.*)$/mi", $headers, $matches)) {
                            $content_type = end($matches[1]);
                        }
                        $camshot_data[self::COLUMN_IMAGE_UUID] = $file->toGUIDv4($file->addFile(
                            "camshot",
                            temp_stream($image_data),
                            [
                                "contentType" => $content_type,
                                "expire" => time() + $this->ttl_camshot_days * 86400,
                            ]
                        ));
                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_FRS;
                        $camshot_data[self::COLUMN_FACE] = [
                            FrsFeature::P_FACE_LEFT => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_LEFT],
                            FrsFeature::P_FACE_TOP => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_TOP],
                            FrsFeature::P_FACE_WIDTH => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_WIDTH],
                            FrsFeature::P_FACE_HEIGHT => $response[FrsFeature::P_DATA][FrsFeature::P_FACE_HEIGHT],
                        ];
                    }
                }

                logger('plog')->debug('frs camshot', ['data' => $camshot_data]);

                if (!$camshot_data) {
                    //получение кадра с DVR-серевера, если нет кадра от FRS
                    $prefix = $cameras[0]["dvrStream"];

                    if ($prefix) {
                        $ts_event = $date - $this->back_time_shift_video_shot;
                        $filename = "/tmp/" . uniqid('camshot_') . ".jpeg";

                        $urlOfScreenshot = container(DvrFeature::class)->getUrlOfScreenshot($cameras[0], $ts_event, true);

                        logger('plog')->debug('dvr camshot', ['url' => $urlOfScreenshot]);

                        if (str_contains($urlOfScreenshot, '.mp4')) {
                            shell_exec("ffmpeg -y -i " . $urlOfScreenshot . " -vframes 1 $filename 1>/dev/null 2>/dev/null");
                        } else {
                            file_put_contents($filename, file_get_contents($urlOfScreenshot));
                        }

                        if (file_exists($filename)) {
                            $camshot_data[self::COLUMN_IMAGE_UUID] = $file->toGUIDv4($file->addFile(
                                "camshot",
                                fopen($filename, 'rb'),
                                [
                                    "contentType" => "image/jpeg",
                                    "expire" => time() + $this->ttl_camshot_days * 86400,
                                ]
                            ));

                            unlink($filename);

                            $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_DVR;
                        } else {
                            $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                        }
                    } else {
                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                    }

                    logger('plog')->debug('dvr camshot', ['data' => $camshot_data]);
                }
            }
        }

        return $camshot_data;
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
                $this->clickhouse->insert("plog", [$event_data]);
            }
        } else {
            $hidden = $this->getPlogHidden($event_data[self::COLUMN_FLAT_ID]);

            if ($hidden < 0) {
                return;
            }

            $event_data[self::COLUMN_HIDDEN] = $hidden;

            $this->clickhouse->insert("plog", [$event_data]);
        }
    }

    /**
     * @inheritDoc
     */
    public function addCallDoneData(int $date, string $ip, ?int $call_id): void
    {
        try {
            task(new PlogCallTask($this->getDomophoneId($ip), $ip, $date, $call_id))->delay(15)->default()->dispatch();
        } catch (Throwable $e) {
            logger('task')->error('Error addCallDoneData' . PHP_EOL . $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function addDoorOpenData(int $date, string $ip, int $event_type, int $door, string $detail): void
    {
        try {
            task(new PlogOpenTask($this->getDomophoneId($ip), $event_type, $door, $date, $detail))->delay(15)->default()->dispatch();
        } catch (Throwable $e) {
            logger('task')->error('Error addDoorOpenData' . PHP_EOL . $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function addDoorOpenDataById(int $date, int $domophone_id, int $event_type, int $door, string $detail): void
    {
        try {
            task(new PlogOpenTask($domophone_id, $event_type, $door, $date, $detail))->delay(15)->default()->dispatch();
        } catch (Throwable $e) {
            logger('task')->error('Error addDoorOpenDataById' . PHP_EOL . $e);
        }
    }

    public function getSyslog(string $ip, int $date): false|array
    {
        $database = $this->clickhouse->database;

        $start_date = $date - $this->max_call_length;
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
        $result = container(HouseFeature::class)->getDomophones('ip', $ip);

        if ($result && $result[0])
            return $result[0]['domophoneId'];

        return false;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function getPlogHidden($flat_id): int
    {
        $flat = container(HouseFeature::class)->getFlat($flat_id);

        if ($flat['plog'] == self::ACCESS_RESTRICTED_BY_ADMIN)
            return -1;

        $hidden = 0;

        if ($flat['plog'] == self::ACCESS_DENIED)
            $hidden = 1;

        return $hidden;
    }
}