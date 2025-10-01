<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule;

use Selpol\Entity\Model\Schedule;
use Selpol\Feature\Feature;
use Selpol\Feature\Schedule\Internal\InternalScheduleFeature;
use Selpol\Feature\Schedule\Internal\Statement\StatementResult;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalScheduleFeature::class)]
readonly abstract class ScheduleFeature extends Feature
{
    public abstract function check(Schedule $schedule): void;

    public abstract function execute(Schedule $schedule, ScheduleTimeInterface $time): StatementResult;
}
