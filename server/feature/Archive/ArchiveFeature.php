<?php

namespace Selpol\Feature\Archive;

use Selpol\Entity\Model\Dvr\DvrRecord;
use Selpol\Feature\Archive\Internal\InternalArchiveFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalArchiveFeature::class)]
readonly abstract class ArchiveFeature extends Feature
{
    public abstract function addDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish, int $state = 0): int;

    public abstract function checkDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): ?DvrRecord;

    public abstract function exportDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): string|false;

    public abstract function runDownloadRecordTask(int $recordId): bool|string;
}