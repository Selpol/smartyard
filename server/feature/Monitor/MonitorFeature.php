<?php declare(strict_types=1);

namespace Selpol\Feature\Monitor;

use Selpol\Feature\Feature;
use Selpol\Feature\Monitor\Internal\InternalMonitorFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalMonitorFeature::class)]
readonly abstract class MonitorFeature extends Feature
{
    public abstract function status(int $id): array;

    public abstract function ping(int $id): bool;

    public abstract function sip(int $id): bool;
}