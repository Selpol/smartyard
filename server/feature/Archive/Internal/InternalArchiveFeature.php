<?php

namespace Selpol\Feature\Archive\Internal;

use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\File\FileFeature;
use Throwable;

class InternalArchiveFeature extends ArchiveFeature
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
                $cam = container(CameraFeature::class)->getCamera($task['cameraId']);

                if (!$cam) {
                    echo "Camera with id = " . $task['cameraId'] . " was not found\n";
                    return false;
                }
                $request_url = container(DvrFeature::class)->getUrlOfRecord($cam, $task['subscriberId'], $task['start'], $task['finish']);

                $this->getDatabase()->modify("update camera_records set state = 1 where record_id = $recordId");

                echo "Record download task with id = $recordId was started\n";
                echo "Fetching record form $request_url to " . $task['filename'] . "\n";

                $arrContextOptions = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));

                $file = fopen($request_url, "r", false, stream_context_create($arrContextOptions));
                $fileId = container(FileFeature::class)->addFile($task['filename'], $file, [
                    "camId" => $task['cameraId'],
                    "start" => $task['start'],
                    "finish" => $task['finish'],
                    "subscriberId" => $task['subscriberId'],
                    "expire" => $task['expire']
                ]);

                if ($file) {
                    $this->getDatabase()->modify("update camera_records set state = 2 where record_id = $recordId");
                    echo "Record download task with id = $recordId was successfully finished!\n";
                    fclose($file);

                    return $fileId;
                } else {
                    $this->getDatabase()->modify("update camera_records set state = 3 where record_id = $recordId");

                    echo "Record download task with id = $recordId was finished with error!\n";

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