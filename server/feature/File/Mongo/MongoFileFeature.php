<?php

namespace Selpol\Feature\File\Mongo;

use Exception;
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
            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(true))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id);
            }

            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(false))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id);
            }
        }

        return true;
    }

    public function getCount(): ?int
    {
        $cursor = container(MongoService::class)->getDatabase($this->database)->command(['dbStats' => 1]);
        $value = iterator_to_array($cursor);

        return $value[0]['objects'];
    }

    public function addFile(string $realFileName, $stream, array $metadata = [], bool $archive = false): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();

        $id = $bucket->uploadFromStream($realFileName, $stream);

        if ($metadata) {
            $this->setFileMetadata($id, $metadata);
        }

        return (string) $id;
    }

    public function getFile(string $uuid, bool $archive = false): array
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();

        $fileId = new ObjectId($uuid);

        $stream = $bucket->openDownloadStream($fileId);

        return ["fileInfo" => $bucket->getFileDocumentForStream($stream), "stream" => $stream];
    }

    public function getFileSize(string $uuid, bool $archive = false): int
    {
        $value = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->{"fs.files"}->findOne(['_id' => new ObjectId($uuid)], ['projection' => ['length' => true]]);

        if ($value) {
            if ($value instanceof BSONDocument) {
                return $value->offsetGet('length') ?? 0;
            } else if (array_key_exists('length', $value)) {
                return $value['length'];
            }
        }

        return 0;
    }

    public function getFileBytes(string $uuid, bool $archive = false): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();

        return stream_get_contents($bucket->openDownloadStream(new ObjectId($uuid)));
    }

    public function getFileStream(string $uuid, bool $archive = false)
    {
        return $this->getFile($uuid, $archive)["stream"];
    }

    public function getFileInfo(string $uuid, bool $archive = false): object
    {
        return $this->getFile($uuid, $archive)["fileInfo"];
    }

    public function setFileMetadata(string $uuid, array $metadata, bool $archive = false): UpdateResult
    {
        return container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->{"fs.files"}->updateOne(["_id" => new ObjectId($uuid)], ['$set' => ["metadata" => $metadata]]);
    }

    public function getFileMetadata(string $uuid, bool $archive = false): array
    {
        return $this->getFileInfo($uuid)->metadata;
    }

    public function searchFiles(array $query, bool $archive = false): array
    {
        $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->{"fs.files"}->find($query, ["sort" => ["filename" => 1]]);

        $files = [];

        foreach ($cursor as $document) {
            $document = json_decode(json_encode($document), true);
            $document["id"] = (string) $document["_id"]["\$oid"];

            unset($document["_id"]);

            $files[] = $document;
        }

        return $files;
    }

    public function deleteFile(string $uuid, bool $archive = false): bool
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();

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

    private function getDatabaseName(bool $archive): string
    {
        if ($archive) {
            return $this->database . '_archive';
        }

        return $this->database;
    }
}