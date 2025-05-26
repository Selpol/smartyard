<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule;

final readonly class AlwaysScheduleTime implements ScheduleTimeInterface
{
    public function at(string $value): bool
    {
        return true;
    }

    public function minutely(): bool
    {
        return true;
    }

    public function hourly(): bool
    {
        return true;
    }

    public function daily(): bool
    {
        return true;
    }

    public function monthly(): bool
    {
        return true;
    }
}
