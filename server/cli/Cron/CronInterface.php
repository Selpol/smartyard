<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

use Selpol\Feature\Schedule\ScheduleTime;

interface CronInterface
{
    public function cron(ScheduleTime $value): bool;
}