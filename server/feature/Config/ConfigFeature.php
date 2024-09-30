<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use Selpol\Feature\Config\Internal\InternalConfigFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalConfigFeature::class)]
readonly abstract class ConfigFeature extends Feature
{
    /**
     * @return ConfigItem[]
     */
    public abstract function getConfigForIntercomDescription(): array;

    public abstract function getConfigForIntercom(): Config;
}