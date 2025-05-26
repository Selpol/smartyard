<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

use Selpol\Feature\Schedule\ScheduleTimeInterface;

interface CronInterface
{
    public function cron(ScheduleTimeInterface $value): bool;
}