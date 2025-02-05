<?php

namespace Selpol\Feature\File;

use FileType;
use MongoDB\UpdateResult;
use Selpol\Cli\Cron\CronInterface;
use Selpol\Feature\Feature;
use Selpol\Feature\File\Mongo\MongoFileFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(MongoFileFeature::class)]
readonly abstract class FileFeature extends Feature implements CronInterface
{
    const DEFAULT_DATABASE = 'rbt';

    /**
     * @param string $realFileName
     * @param resource $stream
     * @param array $metadata
     * @return string
     */
    abstract public function addFile(string $realFileName, $stream, array $metadata = [], FileType $type = FileType::Other): string;

    abstract public function getFile(string $uuid, FileType $type = FileType::Other): array;

    abstract public function getFileSize(string $uuid, FileType $type = FileType::Other): int;

    abstract public function getFileBytes(string $uuid, FileType $type = FileType::Other): string;

    /**
     * @param string $uuid
     * @return resource
     */
    abstract public function getFileStream(string $uuid, FileType $type = FileType::Other);

    abstract public function getFileInfo(string $uuid, FileType $type = FileType::Other): object;

    abstract public function setFileMetadata(string $uuid, array $metadata, FileType $type = FileType::Other): UpdateResult;

    abstract public function getFileMetadata(string $uuid, FileType $type = FileType::Other): array;

    abstract public function searchFiles(array $query, FileType $type = FileType::Other): array;

    abstract public function deleteFile(string $uuid, FileType $type = FileType::Other): bool;

    abstract public function toGUIDv4(string $uuid): string;

    abstract public function fromGUIDv4(string $guidv4): string;

    abstract public function getDatabaseName(FileType $type = FileType::Other): string;
}