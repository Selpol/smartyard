<?php

namespace Selpol\Feature\Archive\Internal;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\File\File;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileInfo;
use Selpol\Feature\File\FileMetadata;
use Selpol\Feature\File\FileStorage;
use Selpol\Service\PrometheusService;
use Throwable;

readonly class InternalArchiveFeature extends ArchiveFeature
{
    public function addDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): bool|int|string
    {
        $dvr_files_ttl = config_get('feature.archive.dvr_files_ttl', 259200);

        $filename = guid_v4() . '.mp4';

        return $this->getDatabase()->insert("insert into camera_records (camera_id, subscriber_id, start, finish, filename, expire, state) values (:camera_id, :subscriber_id, :start, :finish, :filename, :expire, :state)", [
            "camera_id" => $cameraId,
            "subscriber_id" => $subscriberId,
            "start" => $start,
            "finish" => $finish,
            "filename" => $filename,
            "expire" => time() + $dvr_files_ttl,
            "state" => 0 //0 = created, 1 = in progress, 2 = completed, 3 = error
        ]);
    }

    public function checkDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): array|false
    {
        return $this->getDatabase()->get(
            "select record_id from camera_records where camera_id = :camera_id and subscriber_id = :subscriber_id AND start = :start AND finish = :finish",
            [":camera_id" => $cameraId, ":subscriber_id" => $subscriberId, ":start" => $start, ":finish" => $finish],
            ["record_id" => "id"],
            ["singlify"]
        );
    }

    public function exportDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): bool|\Psr\Http\Message\StreamInterface
    {
        $cam = DeviceCamera::findById($cameraId, setting: setting()->nonNullable())->toOldArray();

        $request_url = container(DvrFeature::class)->getUrlOfRecord($cam, $subscriberId, $start, $finish);

        $arrContextOptions = ["ssl" => ["verify_peer" => false, "verify_peer_name" => false]];

        $resource = fopen($request_url, "r", false, stream_context_create($arrContextOptions));

        return stream($resource);
    }

    public function runDownloadRecordTask(int $recordId): bool|string
    {
        try {
            $task = $this->getDatabase()->get(
                "select camera_id, subscriber_id, start, finish, filename, expire, state from camera_records where record_id = :record_id AND state = 0",
                [
                    ":record_id" => $recordId,
                ],
                [
                    "camera_id" => "cameraId",
                    "subscriber_id" => "subscriberId",
                    "start" => "start",
                    "finish" => "finish",
                    "filename" => "filename",
                    "expire" => "expire",
                    "state" => "state" //0 = created, 1 = in progress, 2 = completed, 3 = error
                ],
                [
                    "singlify"
                ]
            );

            if ($task) {
                $cam = DeviceCamera::findById($task['cameraId'], setting: setting()->nonNullable())->toOldArray();

                if (!$cam) {
                    echo "Camera with id = " . $task['cameraId'] . " was not found\n";
                    return false;
                }
                $request_url = container(DvrFeature::class)->getUrlOfRecord($cam, $task['subscriberId'], $task['start'], $task['finish']);

                $this->getDatabase()->modify("update camera_records set state = 1 where record_id = $recordId");

                echo "Record download task with id = $recordId was started\n";
                echo "Fetching record form $request_url to " . $task['filename'] . "\n";

                $arrContextOptions = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));

                $start = time();

                $resource = fopen($request_url, "r", false, stream_context_create($arrContextOptions));

                $fileId = container(FileFeature::class)->addFile(
                    File::stream(stream($resource))
                        ->withFilename($task['filename'])
                        ->withMetadata(
                            FileMetadata::contentType(null)
                                ->withCameraId($task['cameraId'])
                                ->withSubscirberId($task['subscriberId'])
                                ->withStart($task['start'])
                                ->withEnd($task['finish'])
                                ->withExpire($task['expire'])
                        ),
                    FileStorage::Archive
                );

                $finish = time() - $start;

                file_logger('record')->debug('Finish load file', ['finish' => $finish]);

                $time = container(PrometheusService::class)->getCounter('record', 'time', 'Record download time', ['camera', 'subscriber', 'status']);

                if ($resource) {
                    $this->getDatabase()->modify("update camera_records set state = 2 where record_id = $recordId");
                    echo "Record download task with id = $recordId was successfully finished!\n";

                    fclose($resource);

                    $time->incBy($task['finish'] - $task['start'], [$task['cameraId'], $task['subscriberId'], 1]);

                    return $fileId;
                } else {
                    $this->getDatabase()->modify("update camera_records set state = 3 where record_id = $recordId");

                    echo "Record download task with id = $recordId was finished with error!\n";

                    $time->incBy($task['finish'] - $task['start'], [$task['cameraId'], $task['subscriberId'], 0]);

                    return false;
                }
            } else {
                echo "Task with id = $recordId was not found\n";

                return false;
            }
        } catch (Throwable) {
            echo "Record download task with id = $recordId was failed to start\n";

            return false;
        }
    }
}