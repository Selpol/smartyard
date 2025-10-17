<?php

namespace Selpol\Feature\Archive\Internal;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Feature\Archive\ArchiveFeature;
use Selpol\Feature\Dvr\DvrFeature;
use Selpol\Feature\File\File;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileMetadata;
use Selpol\Feature\File\FileStorage;
use Selpol\Service\PrometheusService;
use Throwable;

readonly class InternalArchiveFeature extends ArchiveFeature
{
    public function addDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish, int $state = 0): int
    {
        $dvr_files_ttl = config_get('feature.archive.dvr_files_ttl', 259200);

        $filename = guid_v4() . '.mp4';

        $record = new DvrRecord();

        $record->camera_id = $cameraId;
        $record->subscriber_id = $subscriberId;
        $record->start = $start;
        $record->finish = $finish;
        $record->filename = $filename;
        $record->expire = time() + $dvr_files_ttl;
        $record->state = $state;

        $record->insert();

        return $record->record_id;
    }

    public function checkDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): ?DvrRecord
    {
        return DvrRecord::fetch(criteria()->equal('camera_id', $cameraId)->equal('subscriber_id', $subscriberId)->equal('start', $start)->equal('finish', $finish));
    }

    public function exportDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): string|false
    {
        try {
            $cam = DeviceCamera::findById($cameraId, setting: setting()->nonNullable())->toOldArray();

            $dvr = container(DvrFeature::class)->getDVRServerByCamera($cam);
            $url = container(DvrFeature::class)->getUrlOfRecord($cam, $subscriberId, $start, $finish);

            if ($dvr && $dvr->type == 'flussonic') {
                return $url;
            }

            return false;
        } catch (Throwable $throwable) {
            file_logger('archive')->error($throwable);

            return false;
        }
    }

    public function runDownloadRecordTask(int $recordId): bool|string
    {
        try {
            $record = DvrRecord::findById($recordId);

            if ($record) {
                $camera = $record->camera->toOldArray();

                $request_url = container(DvrFeature::class)->getUrlOfRecord($camera, $record->subscriber_id, $record->start, $record->finish);

                $this->getDatabase()->modify("update camera_records set state = 1 where record_id = $recordId");

                echo "Record download task with id = $recordId was started\n";
                echo "Fetching record form $request_url to " . $record->filename . "\n";

                $arrContextOptions = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));

                $start = time();

                $resource = fopen($request_url, "r", false, stream_context_create($arrContextOptions));

                $fileId = container(FileFeature::class)->addFile(
                    File::stream(stream($resource))
                        ->withFilename($record->filename)
                        ->withMetadata(
                            FileMetadata::contentType(null)
                                ->withCameraId($record->camera_id)
                                ->withSubscirberId($record->subscriber_id)
                                ->withStart($record->start)
                                ->withEnd($record->finish)
                                ->withExpire($record->expire)
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

                    $time->incBy($record->finish - $record->start, [$record->camera_id, $record->subscriber_id, 1]);

                    return $fileId;
                } else {
                    $this->getDatabase()->modify("update camera_records set state = 3 where record_id = $recordId");

                    echo "Record download task with id = $recordId was finished with error!\n";

                    $time->incBy($record->finish - $record->start, [$record->camera_id, $record->subscriber_id, 0]);

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