<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

final readonly class CronValue
{
    /**
     * Значение от 0 до 59
     * @var int
     */
    public int $minute;

    /**
     * Значения от 0 до 23
     * @var int
     */
    public int $hour;

    /**
     * Значения от 1 до 31
     * @var int
     */
    public int $day;

    /**
     * Значения от 1 до 12
     * @var int
     */
    public int $month;

    /**
     * Значения от 0 до 6. Воскресенье = 0
     * @var int
     */
    public int $weekday;

    public function __construct(int $minute, int $hour, int $day, int $month, int $weekday)
    {
        $this->minute = $minute;
        $this->hour = $hour;
        $this->day = $day;
        $this->month = $month;

        $this->weekday = $weekday;
    }

    public function at(string $value): bool
    {
        if ($value == '* * * * *') {
            return true;
        }

        $segments = explode(' ', $value);

        if (count($segments) > 0 && $segments[0] != '*' && !$this->validate($segments[0], $this->minute)) {
            return false;
        }

        if (count($segments) > 1 && $segments[1] != '*' && !$this->validate($segments[1], $this->hour)) {
            return false;
        }

        if (count($segments) > 2 && $segments[2] != '*' && !$this->validate($segments[2], $this->day)) {
            return false;
        }

        if (count($segments) > 3 && $segments[3] != '*' && !$this->validate($segments[3], $this->month)) {
            return false;
        }

        if (count($segments) > 4 && $segments[4] != '*' && !$this->validate($segments[4], $this->weekday)) {
            return false;
        }

        return true;
    }

    public function minutely(): bool
    {
        return $this->at('* * * * *');
    }

    public function hourly(): bool
    {
        return $this->at('0 * * * *');
    }

    public function daily(): bool
    {
        return $this->at('0 0 * * *');
    }

    public function monthly(): bool
    {
        return $this->at('0 0 1 * *');
    }

    private function validate(string $target, int $source): bool
    {
        if (str_starts_with($target, '*/')) {
            $value = intval(substr($target, 2));

            return ($source % $value) == 0;
        }

        $parts = explode(',', $target);

        foreach ($parts as $part) {
            if (str_contains('-', $part)) {
                [$left, $right] = explode('-', $part);

                if ($source < intval($left) || $source > intval($right)) {
                    continue;
                }
            } else if (intval($part) != $source) {
                continue;
            }

            return true;
        }

        return false;
    }
}
