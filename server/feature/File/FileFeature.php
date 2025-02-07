<?php

namespace Selpol\Feature\File;

use Selpol\Cli\Cron\CronInterface;
use Selpol\Feature\Feature;
use Selpol\Feature\File\Mongo\MongoFileFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(MongoFileFeature::class)]
readonly abstract class FileFeature extends Feature implements CronInterface
{
    public const DEFAULT_DATABASE = 'rbt';

    abstract public function addFile(File $file, FileStorage $storage = FileStorage::Other): string;

    abstract public function getFile(string $uuid, FileStorage $storage = FileStorage::Other): File;

    abstract public function deleteFile(string $uuid, FileStorage $storage = FileStorage::Other): bool;

    /**
     * Search file
     * @param array $query
     * @param FileStorage $storage
     * @return string[]
     */
    abstract public function searchFiles(array $query, FileStorage $storage = FileStorage::Other): array;

    abstract public function toGUIDv4(string $uuid): string;

    abstract public function fromGUIDv4(string $guidv4): string;

    abstract public function getDatabaseName(FileStorage $storage = FileStorage::Other): string;
}