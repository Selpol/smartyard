<?php declare(strict_types=1);

namespace Selpol\Feature\Geo;

use Selpol\Feature\Feature;
use Selpol\Feature\Geo\DaData\DaDataGeoFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(DaDataGeoFeature::class)]
readonly abstract class GeoFeature extends Feature
{
    public abstract function suggestions(string $search, ?string $bound = null): bool|array;
}