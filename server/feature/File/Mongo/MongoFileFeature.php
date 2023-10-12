<?php

namespace Selpol\Feature\File\Mongo;

use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\UpdateResult;
use Selpol\Feature\File\FileFeature;

class MongoFileFeature extends FileFeature
{
    private Client $mongo;
    private string $dbName;

    public function __construct()
    {
        $file = config_get('feature.file');

        $this->dbName = $file["db"] ?: "rbt";

        if ($file["uri"]) $this->mongo = new Client($file["uri"]);
        else $this->mongo = new Client();
    }

    public function addFile(string $realFileName, $stream, array $metadata = []): string
    {
        $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

        $id = $bucket->uploadFromStream($realFileName, $stream);

        if ($metadata)
            $this->setFileMetadata($id, $metadata);

        return (string)$id;
    }

    public function getFile(string $uuid): array
    {
        $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

        $fileId = new ObjectId($uuid);

        $stream = $bucket->openDownloadStream($fileId);

        return ["fileInfo" => $bucket->getFileDocumentForStream($stream), "stream" => $stream];
    }

    public function getFileBytes(string $uuid): string
    {
        $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

        return stream_get_contents($bucket->openDownloadStream(new ObjectId($uuid)));
    }

    public function getFileStream(string $uuid)
    {
        return $this->getFile($uuid)["stream"];
    }

    public function getFileInfo(string $uuid): object
    {
        return $this->getFile($uuid)["fileInfo"];
    }

    public function setFileMetadata(string $uuid, array $metadata): UpdateResult
    {
        return $this->mongo->{$this->dbName}->{"fs.files"}->updateOne(["_id" => new ObjectId($uuid)], ['$set' => ["metadata" => $metadata]]);
    }

    public function getFileMetadata(string $uuid): array
    {
        return $this->getFileInfo($uuid)->metadata;
    }

    public function searchFiles(array $query): array
    {
        $cursor = $this->mongo->{$this->dbName}->{"fs.files"}->find($query, ["sort" => ["filename" => 1]]);

        $files = [];

        foreach ($cursor as $document) {
            $document = json_decode(json_encode($document), true);
            $document["id"] = (string)$document["_id"]["\$oid"];

            unset($document["_id"]);

            $files[] = $document;
        }

        return $files;
    }

    public function deleteFile(string $uuid): bool
    {
        $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

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
}