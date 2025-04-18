<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal;

use Selpol\Feature\Schedule\ScheduleTime;
use Selpol\Framework\Kernel\Exception\KernelException;

class Context
{
    public ScheduleTime $time;

    public array $store;

    public function __construct(ScheduleTime $time)
    {
        $this->time = $time;

        $this->store = [];
    }

    public function get(string $key): mixed
    {
        return array_key_exists($key, $this->store) ? $this->store[$key] : null;
    }

    public function getOrThrow(string $key): mixed
    {
        if (array_key_exists($key, $this->store)) {
            return $this->store[$key];
        }

        throw new KernelException('Неизвестное значение');
    }

    public function set(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }
}
