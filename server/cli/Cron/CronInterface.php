<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

interface CronInterface
{
    public function cron(CronValue $value): bool;
}