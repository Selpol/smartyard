<?php

namespace Selpol\Feature\Dvr;

use Selpol\Feature\Feature;

abstract class DvrFeature extends Feature
{
    public abstract function getDVRServerByStream(string $url): array;

    public abstract function getDVRTokenForCam(array $cam, int $subscriberId): string;

    public abstract function getDVRServers(): array;

    public abstract function getUrlOfRecord(array $cam, int $subscriberId, int $start, int $finish): string|bool;

    public abstract function getUrlOfScreenshot(array $cam, int $time, string|bool $addTokenToUrl = false): string|bool;
}