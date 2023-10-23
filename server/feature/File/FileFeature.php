<?php

namespace Selpol\Feature\File;

use MongoDB\UpdateResult;
use Selpol\Feature\Feature;
use Selpol\Feature\File\Mongo\MongoFileFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(MongoFileFeature::class)]
readonly abstract class FileFeature extends Feature
{
    /**
     * @param string $realFileName
     * @param resource $stream
     * @param array $metadata
     * @return string
     */
    abstract public function addFile(string $realFileName, $stream, array $metadata = []): string;

    abstract public function getFile(string $uuid): array;

    abstract public function getFileBytes(string $uuid): string;

    /**
     * @param string $uuid
     * @return resource
     */
    abstract public function getFileStream(string $uuid);

    abstract public function getFileInfo(string $uuid): object;

    abstract public function setFileMetadata(string $uuid, array $metadata): UpdateResult;

    abstract public function getFileMetadata(string $uuid): array;

    abstract public function searchFiles(array $query): array;

    abstract public function deleteFile(string $uuid): bool;

    abstract public function toGUIDv4(string $uuid): string;

    abstract public function fromGUIDv4(string $guidv4): string;
}