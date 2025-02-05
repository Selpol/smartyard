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
<<<<<<< HEAD
            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(FileType::Archive))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);
=======
            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(true))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);

            foreach ($cursor as $document) {
                $this->deleteFile($document->_id);
            }

            $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName(false))->{"fs.files"}->find(['metadata.expire' => ['$lt' => time()]]);
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

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
<<<<<<< HEAD
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();
=======
        $cursor = container(MongoService::class)->getDatabase($this->database)->command(['dbStats' => 1]);
        $value = iterator_to_array($cursor);

        return $value[0]['objects'];
    }

    public function addFile(string $realFileName, $stream, array $metadata = [], bool $archive = false): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

        $id = $bucket->uploadFromStream($realFileName, $stream);

        if ($metadata) {
            $this->setFileMetadata($id, $metadata, $type);
        }

        return (string) $id;
    }

<<<<<<< HEAD
    public function getFile(string $uuid, FileType $type = FileType::Other): array
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();
=======
    public function getFile(string $uuid, bool $archive = false): array
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

        $fileId = new ObjectId($uuid);

        $stream = $bucket->openDownloadStream($fileId);

        return ["fileInfo" => $bucket->getFileDocumentForStream($stream), "stream" => $stream];
    }

<<<<<<< HEAD
    public function getFileSize(string $uuid, FileType $type = FileType::Other): int
    {
        $value = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->{"fs.files"}->findOne(['_id' => new ObjectId($uuid)], ['projection' => ['length' => true]]);
=======
    public function getFileSize(string $uuid, bool $archive = false): int
    {
        $value = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->{"fs.files"}->findOne(['_id' => new ObjectId($uuid)], ['projection' => ['length' => true]]);
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

        if ($value) {
            if ($value instanceof BSONDocument) {
                return $value->offsetGet('length') ?? 0;
            } else if (array_key_exists('length', $value)) {
                return $value['length'];
            }
        }

        return 0;
    }

<<<<<<< HEAD
    public function getFileBytes(string $uuid, FileType $type = FileType::Other): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();
=======
    public function getFileBytes(string $uuid, bool $archive = false): string
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

        return stream_get_contents($bucket->openDownloadStream(new ObjectId($uuid)));
    }

<<<<<<< HEAD
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
=======
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
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d
    {
        return $this->getFileInfo($uuid)->metadata;
    }

<<<<<<< HEAD
    public function searchFiles(array $query, FileType $type = FileType::Other): array
    {
        $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->{"fs.files"}->find($query, ["sort" => ["filename" => 1]]);
=======
    public function searchFiles(array $query, bool $archive = false): array
    {
        $cursor = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->{"fs.files"}->find($query, ["sort" => ["filename" => 1]]);
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

        $files = [];

        foreach ($cursor as $document) {
            $document = json_decode(json_encode($document), true);
            $document["id"] = (string) $document["_id"]["\$oid"];

            unset($document["_id"]);

            $files[] = $document;
        }

        return $files;
    }

<<<<<<< HEAD
    public function deleteFile(string $uuid, FileType $type = FileType::Other): bool
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($type))->selectGridFSBucket();
=======
    public function deleteFile(string $uuid, bool $archive = false): bool
    {
        $bucket = container(MongoService::class)->getDatabase($this->getDatabaseName($archive))->selectGridFSBucket();
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d

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

<<<<<<< HEAD
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
=======
    private function getDatabaseName(bool $archive): string
    {
        if ($archive) {
            return $this->database . '_archive';
        }

        return $this->database;
>>>>>>> 5ac0c085f357ae0558ce7a26aeb884130ff1a69d
    }
}