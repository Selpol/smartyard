<?php

declare(strict_types=1);

namespace Selpol\Feature\Monitor;

use Selpol\Cli\Cron\CronInterface;
use Selpol\Cli\Cron\CronTag;
use Selpol\Feature\Feature;
use Selpol\Feature\Monitor\Internal\InternalMonitorFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[CronTag]
#[Singleton(InternalMonitorFeature::class)]
readonly abstract class MonitorFeature extends Feature implements CronInterface
{
    public abstract function status(int $id): bool;

    public abstract function sip(int $id): bool;
}
