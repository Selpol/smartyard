<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

enum CronEnum
{
    case minutely;
    case min5;
    case hourly;
    case daily;
    case monthly;

    public static function from(string $value): ?CronEnum
    {
        return match ($value) {
            "minutely" => CronEnum::minutely,
            "5min" => CronEnum::min5,
            "hourly" => CronEnum::hourly,
            "daily" => CronEnum::daily,
            "monthly" => CronEnum::monthly,

            default => null
        };
    }
}