<?php

namespace Selpol\Feature\File;

use MongoDB\UpdateResult;
use Selpol\Cli\Cron\CronInterface;
use Selpol\Feature\Feature;
use Selpol\Feature\File\Mongo\MongoFileFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(MongoFileFeature::class)]
readonly abstract class FileFeature extends Feature implements CronInterface
{
    const DEFAULT_DATABASE = 'rbt';

    abstract public function getCount(): ?int;

    /**
     * @param string $realFileName
     * @param resource $stream
     * @param array $metadata
     * @return string
     */
    abstract public function addFile(string $realFileName, $stream, array $metadata = [], bool $archive = false): string;

    abstract public function getFile(string $uuid, bool $archive = false): array;

    abstract public function getFileSize(string $uuid, bool $archive = false): int;

    abstract public function getFileBytes(string $uuid, bool $archive = false): string;

    /**
     * @param string $uuid
     * @return resource
     */
    abstract public function getFileStream(string $uuid, bool $archive = false);

    abstract public function getFileInfo(string $uuid, bool $archive = false): object;

    abstract public function setFileMetadata(string $uuid, array $metadata, bool $archive = false): UpdateResult;

    abstract public function getFileMetadata(string $uuid, bool $archive = false): array;

    abstract public function searchFiles(array $query, bool $archive = false): array;

    abstract public function deleteFile(string $uuid, bool $archive = false): bool;

    abstract public function toGUIDv4(string $uuid): string;

    abstract public function fromGUIDv4(string $guidv4): string;
}