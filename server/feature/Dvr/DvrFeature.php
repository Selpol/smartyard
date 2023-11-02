<?php

namespace Selpol\Feature\Dvr;

use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Feature\Dvr\Internal\InternalDvrFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalDvrFeature::class)]
readonly abstract class DvrFeature extends Feature
{
    public abstract function getDVRServerByStream(string $url): ?DvrServer;

    public abstract function getDVRTokenForCam(array $cam, int $subscriberId): string;

    /**
     * @return DvrServer[]
     */
    public abstract function getDVRServers(): array;

    public abstract function getUrlOfRecord(array $cam, int $subscriberId, int $start, int $finish): string|bool;

    public abstract function getUrlOfScreenshot(array $cam, int $time, string|bool $addTokenToUrl = false): string|bool;
}