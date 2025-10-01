<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule;

interface ScheduleTimeInterface
{
    public function getTime(): int;

    public function at(string $value): bool;

    public function minutely(): bool;

    public function hourly(): bool;

    public function daily(): bool;

    public function monthly(): bool;
}
