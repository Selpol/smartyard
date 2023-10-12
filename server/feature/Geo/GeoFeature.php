<?php

namespace Selpol\Feature\Geo;

use Selpol\Feature\Feature;
use Selpol\Feature\Geo\DaData\DaDataGeoFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(DaDataGeoFeature::class)]
abstract class GeoFeature extends Feature
{
    public abstract function suggestions(string $search): bool|array;
}