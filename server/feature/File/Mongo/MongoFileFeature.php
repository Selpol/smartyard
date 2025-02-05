<?php

namespace Selpol\Feature\File\Mongo;

use Exception;
use FileType;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use MongoDB\UpdateResult;
use Selpol\Cli\Cron\CronEnum;
use Selpol\Feature\File\FileFeature;
use Selpol\Service\MongoService;

readonly class MongoFileFeature extends FileFeature
{
    private string $database;

    public function __construct()
    {
        $this->database = config_get('feature.file.database', self::DEFAULT_DATABASE);
    }

    public function cron(CronEnum $value): bool
    {
        if ($value->name === config_get('feature.file.cron_sync_data_scheduler')) {
            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(FileType::Archive))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id, FileType::Archive);
            }

            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(FileType::Screenshot))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id, FileType::Screenshot);
            }

            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(FileType::OldScreenshot))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id, FileType::OldScreenshot);
            }
        }

        return true;
    }

    public function addFile(string $realFileName, $stream, array $metadata = [], FileType $type = FileType::Other): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

        $id = $bucket->uploadFromStream($realFileName, $stream);

        if ($metadata) {
            $this->setFileMetadata($id, $metadata, $type);
        }

        return (string) $id;
    }

    public function getFile(string $uuid, FileType $type = FileType::Other): array
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

        $fileId = new ObjectId($uuid);

        $stream = $bucket->openDownloadStream($fileId);

        return ["fileInfo" => $bucket->getFileDocumentForStream($stream), "stream" => $stream];
    }

    public function getFileSize(string $uuid, FileType $type = FileType::Other): int
    {
        $value = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->{"fs.files"}->findOne(['_id' => new ObjectId($uuid)], ['projection' => ['length' => true]]);

        if ($value) {
            if ($value instanceof BSONDocument) {
                return $value->offsetGet('length') ?? 0;
            } else if (array_key_exists('length', $value)) {
                return $value['length'];
            }
        }

        return 0;
    }

    public function getFileBytes(string $uuid, FileType $type = FileType::Other): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

        return stream_get_contents($bucket->openDownloadStream(new ObjectId($uuid)));
    }

    public function getFileStream(string $uuid, FileType $type = FileType::Other)
    {
        return $this->getFile($uuid, $type)["stream"];
    }

    public function getFileInfo(string $uuid, FileType $type = FileType::Other): object
    {
        return $this->getFile($uuid, $type)["fileInfo"];
    }

    public function setFileMetadata(string $uuid, array $metadata, FileType $type = FileType::Other): UpdateResult
    {
        return container(MongoService::class)->getDatabase($this->getDatabaseName($type))->{"fs.files"}->updateOne(["_id" => new ObjectId($uuid)], ['$set' => ["metadata" => $metadata]]);
    }

    public function getFileMetadata(string $uuid, FileType $type = FileType::Other): array
    {
        return $this->getFileInfo($uuid)->metadata;
    }

    public function searchFiles(array $query, FileType $type = FileType::Other): array
    {
        $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->{"fs.files"}->find($query, ["sort" => ["filename" => 1]]);

        $files = [];

        foreach ($cursor as $document) {
            $document = json_decode(json_encode($document), true);
            $document["id"] = (string) $document["_id"]["\$oid"];

            unset($document["_id"]);

            $files[] = $document;
        }

        return $files;
    }

    public function deleteFile(string $uuid, FileType $type = FileType::Other): bool
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();

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

    public function getDatabaseName(FileType $type = FileType::Other): string
    {
        return match ($type) {
            FileType::Screenshot => $this->database . '_screenshot',
            FileType::Face => $this->database . '_face',
            FileType::Archive => $this->database . '_archive',
            FileType::Group => $this->database . '_group',
            FileType::OldScreenshot => $this->database,
            FileType::OldFace => $this->database,
            default => $this->database . '_other'
        };
    }
}