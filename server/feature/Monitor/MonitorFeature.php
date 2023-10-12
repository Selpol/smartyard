<?php

namespace Selpol\Feature\Monitor;

use Selpol\Feature\Feature;
use Selpol\Feature\Monitor\Internal\InternalMonitorFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalMonitorFeature::class)]
abstract class MonitorFeature extends Feature
{
    public abstract function ping(int $id): bool;

    public abstract function sip(int $id): bool;
}