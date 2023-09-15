<?php

namespace backends\dvr_exports;

use backends\backend;

abstract class dvr_exports extends backend
{
    abstract public function addDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): bool|int|string;

    abstract public function checkDownloadRecord(int $cameraId, int $subscriberId, int $start, int $finish): array|false;

    abstract public function runDownloadRecordTask(int $recordId): bool|string;
}
