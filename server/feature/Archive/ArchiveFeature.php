<?php

namespace Selpol\Feature\Archive;

use Selpol\Feature\Archive\Internal\InternalArchiveFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalArchiveFeature::class)]
readonly abstract class ArchiveFeature extends Feature
{
    public abstract function addDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): bool|int|string;

    public abstract function checkDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): array|false;

    public abstract function runDownloadRecordTask(int $recordId): bool|string;
}