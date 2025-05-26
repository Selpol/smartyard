<?php

namespace Selpol\Feature\File\Mongo;

use Exception;
use MongoDB\BSON\ObjectId;
use Selpol\Feature\File\File;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileInfo;
use Selpol\Feature\File\FileMetadata;
use Selpol\Feature\File\FileStorage;
use Selpol\Feature\Schedule\ScheduleTimeInterface;
use Selpol\Service\MongoService;

readonly class MongoFileFeature extends FileFeature
{
    private MongoService $service;

    private string $database;

    public function __construct()
    {
        $this->service = container(MongoService::class);

        $this->database = config_get('feature.file.database', self::DEFAULT_DATABASE);
    }

    public function cron(ScheduleTimeInterface $value): bool
    {
        if ($value->daily()) {
            $cursor = $this->service->getDatabase($this->getDatabaseName(FileStorage::Archive))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id, FileStorage::Archive);
            }

            $cursor = $this->service->getDatabase($this->getDatabaseName(FileStorage::Screenshot))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id, FileStorage::Screenshot);
            }
        }

        return true;
    }

    public function addFile(File $file, FileStorage $type = FileStorage::Other): string
    {
        $bucket = $this->service->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

        if ($file->info->filename === null) {
            $file->info->filename = 'upload-' . time();
        }

        $id = $bucket->uploadFromStream($file->info->filename, $file->stream->detach());

        if ($file->info->metadata) {
            $metadata = [];

            if ($file->info->metadata->contentType) {
                $metadata['content_type'] = $file->info->metadata->contentType;
            }

            if ($file->info->metadata->subscirberId) {
                $metadata['subscirber_id'] = $file->info->metadata->subscirberId;
            }

            if ($file->info->metadata->cameraId) {
                $metadata['camera_id'] = $file->info->metadata->cameraId;
            }

            if ($file->info->metadata->faceId) {
                $metadata['face_id'] = $file->info->metadata->faceId;
            }

            if ($file->info->metadata->start) {
                $metadata['start'] = $file->info->metadata->start;
            }

            if ($file->info->metadata->end) {
                $metadata['end'] = $file->info->metadata->end;
            }

            if ($file->info->metadata->expire) {
                $metadata['expire'] = $file->info->metadata->expire;
            }

            $this->service->getDatabase($this->getDatabaseName($type))->{"fs.files"}->updateOne(["_id" => new ObjectId($id)], ['$set' => ["metadata" => $metadata]]);
        }

        return (string) $id;
    }

    public function getFile(string $uuid, FileStorage $type = FileStorage::Other): File
    {
        $bucket = $this->service->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

        $fileId = new ObjectId($uuid);

        $rawStream = $bucket->openDownloadStream($fileId);
        $rawInfo = $bucket->getFileDocumentForStream($rawStream);

        if (isset($rawInfo->metadata)) {
            $rawMetadata = json_decode(json_encode($rawInfo->metadata), true);
            $metadata = new FileMetadata(null, null, null, null, null, null, null);

            if (array_key_exists('content_type', $rawMetadata)) {
                $metadata->withContentType($rawMetadata['content_type']);
            }

            if (array_key_exists('subscirber_id', $rawMetadata)) {
                $metadata->withSubscirberId($rawMetadata['subscirber_id']);
            }

            if (array_key_exists('camera_id', $rawMetadata)) {
                $metadata->withCameraId($rawMetadata['camera_id']);
            }

            if (array_key_exists('face_id', $rawMetadata)) {
                $metadata->withFaceId($rawMetadata['face_id']);
            }

            if (array_key_exists('start', $rawMetadata)) {
                $metadata->withStart($rawMetadata['start']);
            }

            if (array_key_exists('end', $rawMetadata)) {
                $metadata->withEnd($rawMetadata['end']);
            }

            if (array_key_exists('expire', $rawMetadata)) {
                $metadata->withExpire($rawMetadata['expire']);
            }
        } else {
            $metadata = null;
        }

        $info = new FileInfo(
            isset($rawInfo->filename) ? $rawInfo->filename : null,
            isset($rawInfo->length) ? $rawInfo->length : null,
            $metadata,
        );

        return new File(stream($rawStream), $info);
    }

    public function searchFiles(FileInfo|array $info, FileStorage $type = FileStorage::Other): array
    {
        $cursor = $this->service->getDatabase($this->getDatabaseName($type))->{"fs.files"}->find(is_array($info) ? $info : $info->toQuery());

        $files = [];

        foreach ($cursor as $document) {
            $id = $document['_id'];

            $files[] = ($id instanceof ObjectId) ? (string) $id->jsonSerialize()['$oid'] : (string) $id['$oid'];
        }

        return $files;
    }

    public function deleteFile(string $uuid, FileStorage $type = FileStorage::Other): bool
    {
        $bucket = $this->service->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

        if ($bucket) {
            try {
                $bucket->delete(new ObjectId($uuid));

                return true;
            } catch (Exception) {
                return false;
            }
        }

        return false;
    }

    public function toGUIDv4(string $uuid): string
    {
        $uuid = "10001000" . $uuid;

        $hyphen = chr(45);
        return substr($uuid, 0, 8) . $hyphen . substr($uuid, 8, 4) . $hyphen . substr($uuid, 12, 4) . $hyphen . substr($uuid, 16, 4) . $hyphen . substr($uuid, 20, 12);
    }

    public function fromGUIDv4(string $guidv4): string
    {
        return str_replace("-", "", substr($guidv4, 8));
    }

    public function getDatabaseName(FileStorage $type = FileStorage::Other): string
    {
        return match ($type) {
            FileStorage::Screenshot => $this->database . '_screenshot',
            FileStorage::Archive => $this->database . '_archive',
            FileStorage::Group => $this->database . '_group',
            FileStorage::Other => $this->database
        };
    }
}