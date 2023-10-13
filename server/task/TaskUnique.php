<?php declare(strict_types=1);

namespace Selpol\Task;

readonly class TaskUnique
{
    public string $identifier;

    public int $ttl;

    /**
     * @param array<string|float|int|bool> $value
     * @param int $ttl
     */
    public function __construct(array $value, int $ttl = 60)
    {
        $this->identifier = implode('-', $value);

        $this->ttl = $ttl;
    }
}