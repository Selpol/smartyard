<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

interface CronInterface
{
    public function cron(CronEnum $value): bool;
}