<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use Selpol\Feature\Feature;

readonly abstract class ConfigFeature extends Feature
{
    public abstract function getDefaultConfigForIntercom(): Config;
}