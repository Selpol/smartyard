<?php

namespace backends\dvr;

use backends\backend;

abstract class dvr extends backend
{
    abstract public function getDVRServerByStream(string $url): array;

    abstract public function getDVRTokenForCam(array $cam, int $subscriberId): string;

    abstract public function getDVRServers(): array;

    abstract public function getUrlOfRecord(array $cam, int $subscriberId, int $start, int $finish): string|bool;

    abstract public function getUrlOfScreenshot(array $cam, int $time, string|bool $addTokenToUrl = false): string|bool;
}
