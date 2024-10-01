<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Internal\InternalConfigFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalConfigFeature::class)]
readonly abstract class ConfigFeature extends Feature
{
    /**
     * @return ConfigItem[]
     */
    public abstract function getDescriptionForIntercomConfig(): array;

    public abstract function getConfigForIntercom(DeviceIntercom $intercom, bool $cache = true): Config;
}