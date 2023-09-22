<?php

namespace Selpol\Feature\Geo;

use Selpol\Feature\Feature;

abstract class GeoFeature extends Feature
{
    public abstract function suggestions(string $search): bool|array;
}